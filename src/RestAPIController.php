<?php

namespace Nimzero\QuizMosaic;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;
use wpdb;

class RestAPIController extends WP_REST_Controller
{
    /**
     * Le type d'objet associé à ce controller
     *
     * @var string
     */
    protected $object_type = 'survey';
    protected $namespace = 'nz-quizmosaic/v1';

    /**
     * Initialise les routes du controller
     */
    public function register_routes()
    {
        // Route pour la récupération d'un élément
        register_rest_route($this->namespace, '/' . $this->object_type . '/(?P<id>\d+)', array(
            'methods'   => WP_REST_Server::READABLE,
            'callback'  => array($this, 'get_item'),
            'args'      => array(
                'id' => array(
                    'description' => __('ID de l\'élément', 'mon-plugin'),
                    'type'        => 'integer',
                    'required'    => true,
                ),
            ),
            'permission_callback' => '__return_true',
            'schema'    => array($this, 'get_item_schema'),
        ));

        // Route pour la création d'un élément
        register_rest_route($this->namespace, '/' . $this->object_type, array(
            'methods'   => WP_REST_Server::CREATABLE,
            'callback'  => array($this, 'create_item'),
            'schema'    => array($this, 'get_item_schema'),
            'permission_callback' => [$this, 'check_admin_permission'],
        ));

        // Route pour la mise à jour d'un élément
        register_rest_route($this->namespace, '/' . $this->object_type . '/(?P<id>\d+)', array(
            'methods'   => WP_REST_Server::EDITABLE,
            'callback'  => array($this, 'update_item'),
            'args'      => array(
                'id' => array(
                    'description' => __('ID de l\'élément', 'mon-plugin'),
                    'type'        => 'integer',
                    'required'    => true,
                ),
            ),
            'permission_callback' => [$this, 'check_admin_permission'],
            'schema'    => array($this, 'get_item_schema'),
        ));

        // Route pour la suppression d'un élément
        register_rest_route($this->namespace, '/' . $this->object_type . '/(?P<id>\d+)', array(
            'methods'   => WP_REST_Server::DELETABLE,
            'callback'  => array($this, 'delete_item'),
            'args'      => array(
                'id' => array(
                    'description' => __('ID de l\'élément', 'mon-plugin'),
                    'type'        => 'integer',
                    'required'    => true,
                ),
            ),
            'permission_callback' => [$this, 'check_admin_permission'],
            'schema'    => array($this, 'get_item_schema'),
        ));
    }

    /**
     * Vérifie si l'utilisateur est administrateur.
     *
     * @param WP_REST_Request $request L'objet de requête de l'API REST.
     * @return WP_Error|bool Un objet WP_Error si l'utilisateur n'est pas administrateur, sinon true.
     */
    function check_admin_permission( $request ) {
        if ( ! current_user_can( 'administrator' ) ) {
            return new WP_Error( 'rest_forbidden', 'Vous n\'êtes pas autorisé à effectuer cette action.', array( 'status' => 403 ) );
        }
    
        return true;
    }

    /**
     * Récupère un élément
     *
     * @param WP_REST_Request $request L'objet de la requête.
     * @return WP_REST_Response La réponse REST.
     */
    public function get_item($request)
    {
        // Récupérez et retournez l'élément demandé
        $id = $request->get_param('id');

        /** @var wpdb $wpdb */
        global $wpdb;
        $prefix = $wpdb->prefix;

        $survey = $wpdb->get_results(sprintf('SELECT S.name, S.style FROM %snz_quizmosaic_survey AS S WHERE S.id = %s;', $prefix, $id));

        if (count($survey) != 1) {
            return rest_ensure_response(new WP_REST_Response(['message' => 'Le quiz n\'a pas put être touvé.'], status: 404));
        }

        $data = [
            'name' => $survey[0]->name,
            'questions' => [],
            'categories' => [],
            'style' => $survey[0]->style,
        ];

        $categories = $wpdb->get_results(sprintf('SELECT C.id, C.text FROM %snz_quizmosaic_category AS C WHERE C.survey_id = %s;', $prefix, $id));
        foreach ($categories as $category) {
            $data['categories'][$category->id] = $category->text;
        }

        $questions = $wpdb->get_results(sprintf('SELECT Q.id, Q.question FROM %snz_quizmosaic_question AS Q WHERE Q.survey_id = %s;', $prefix, $id));
        foreach ($questions as $question) {
            $data['questions'][] = [
                'id' => $question->id,
                'question' => $question->question,
                'answers' => [],
            ];
        }

        $answers = $wpdb->get_results(sprintf('SELECT A.question_id as question, A.category_id as category, A.text FROM %snz_quizmosaic_answer AS A WHERE A.survey_id = %s', $prefix, $id));
        foreach ($answers as $answer) {
            $index = array_search($answer->question, array_column($data['questions'], 'id'));
            $data['questions'][$index]['answers'][] = [
                'category' => $answer->category,
                'answer' => $answer->text,
            ];
        }

        return rest_ensure_response($data);
    }

    /**
     * Crée un nouvel élément
     *
     * @param WP_REST_Request $request L'objet de la requête.
     * @return WP_REST_Response La réponse REST.
     */
    public function create_item($request)
    {
        // Créez un nouvel élément en utilisant les données fournies dans la requête
        $data = $request->get_json_params();
        $data = json_decode(stripcslashes($data['data']), true);
        
        $survey = [
            'name' => $data['name'],
            'style' => $data['style'],
        ];

        /** @var wpdb $wpdb */
        global $wpdb;
        $prefix = $wpdb->prefix;

        $wpdb->insert(sprintf('%snz_quizmosaic_survey', $prefix), $survey);

        $survey_id = $wpdb->insert_id;

        $categories = [];
        foreach ($data['categories'] as $id => $text) {
            $cat = [
                'id' => $id,
                'survey_id' => $survey_id,
                'text' => $text,
            ];

            $categories[] = $cat;
            $wpdb->insert(sprintf('%snz_quizmosaic_category', $prefix), $cat);
        }

        $questions = [];
        $answers = [];
        foreach ($data['questions'] as $question) {
            $quest = [
                'id' => $question['id'],
                'survey_id' => $survey_id,
                'question' => $question['question'],
            ];

            $questions[] = $quest;
            $wpdb->insert(sprintf('%snz_quizmosaic_question', $prefix), $quest);

            foreach ($question['answers'] as $answer) {
                $ans = [
                    'survey_id' => $survey_id,
                    'question_id' => $question['id'],
                    'category_id' => $answer['category'],
                    'text' => $answer['answer'],
                ];

                $answers[] = $ans;
                $wpdb->insert(sprintf('%snz_quizmosaic_answer', $prefix), $ans);
            }
        }

        $survey['id'] = $survey_id;
        $survey['questions'] = $questions;
        $survey['categories'] = $categories;

        return rest_ensure_response(['message' => 'done', 'data' => $survey]);
    }

    /**
     * Met à jour un élément existant
     *
     * @param WP_REST_Request $request L'objet de la requête.
     * @return WP_REST_Response La réponse REST.
     */
    public function update_item($request)
    {
        // Mettez à jour l'élément avec les nouvelles données fournies dans la requête
        $survey_id = $request->get_param('id');
        $data = $request->get_json_params();
        $data = json_decode(stripcslashes($data['data']), true);
        
        $survey = [
            'name' => $data['name'],
            'style' => $data['style'],
        ];

        /** @var wpdb $wpdb */
        global $wpdb;
        $prefix = $wpdb->prefix;

        $wpdb->query( 'START TRANSACTION' );

        try {
            $wpdb->update(sprintf('%snz_quizmosaic_survey', $prefix), $survey, ['id' => $survey_id]);
            $wpdb->delete(sprintf('%snz_quizmosaic_answer', $prefix), ['survey_id' => $survey_id]);
            $wpdb->delete(sprintf('%snz_quizmosaic_category', $prefix), ['survey_id' => $survey_id]);
            $wpdb->delete(sprintf('%snz_quizmosaic_question', $prefix), ['survey_id' => $survey_id]);

            $categories = [];
            foreach ($data['categories'] as $id => $text) {
                $cat = [
                    'survey_id' => $survey_id,
                    'text' => $text,
                ];

                $wpdb->update(sprintf('%snz_quizmosaic_category', $prefix), $cat, ['id' => $id]);
                $cat['id'] = $id;
                $categories[] = $cat;
            }

            $categories = [];
            foreach ($data['categories'] as $id => $text) {
                $cat = [
                    'id' => $id,
                    'survey_id' => $survey_id,
                    'text' => $text,
                ];

                $categories[] = $cat;
                $wpdb->insert(sprintf('%snz_quizmosaic_category', $prefix), $cat);
            }

            $questions = [];
            $answers = [];
            foreach ($data['questions'] as $question) {
                $quest = [
                    'id' => $question['id'],
                    'survey_id' => $survey_id,
                    'question' => $question['question'],
                ];

                $questions[] = $quest;
                $wpdb->insert(sprintf('%snz_quizmosaic_question', $prefix), $quest);

                foreach ($question['answers'] as $answer) {
                    $ans = [
                        'survey_id' => $survey_id,
                        'question_id' => $question['id'],
                        'category_id' => $answer['category'],
                        'text' => $answer['answer'],
                    ];

                    $answers[] = $ans;
                    $wpdb->insert(sprintf('%snz_quizmosaic_answer', $prefix), $ans);
                }
            }

            $wpdb->query( 'COMMIT' );

            $survey['id'] = $survey_id;
            $survey['questions'] = $questions;
            $survey['categories'] = $categories;
        }
        catch (\Exception $e) {
            $wpdb->query( 'ROLLBACK' );

            // Gérer l'erreur
            return new WP_Error( 'update_error', 'Une erreur s\'est produite lors de la mise à jour de l\'objet.' );
        }

        return rest_ensure_response(['message' => 'done', 'data' => $survey]);
    }

    /**
     * Supprime un élément existant
     *
     * @param WP_REST_Request $request L'objet de la requête.
     * @return WP_REST_Response La réponse REST.
     */
    public function delete_item($request)
    {
        // Supprimez l'élément avec l'ID fourni dans la requête
        $survey_id = $request->get_param('id');

        /** @var wpdb $wpdb */
        global $wpdb;
        $prefix = $wpdb->prefix;

        $wpdb->query( 'START TRANSACTION' );

        try {
            $wpdb->delete(sprintf('%snz_quizmosaic_answer', $prefix), ['survey_id' => $survey_id]);
            $wpdb->delete(sprintf('%snz_quizmosaic_category', $prefix), ['survey_id' => $survey_id]);
            $wpdb->delete(sprintf('%snz_quizmosaic_question', $prefix), ['survey_id' => $survey_id]);
            $wpdb->delete(sprintf('%snz_quizmosaic_survey', $prefix), ['id' => $survey_id]);
            $wpdb->query( 'COMMIT' );
        }
        catch (\Exception $e) {
            $wpdb->query( 'ROLLBACK' );

            // Gérer l'erreur
            return new WP_Error( 'update_error', 'Une erreur s\'est produite lors de la suppression de l\'objet.' );
        }

        return rest_ensure_response(array('success' => true));
    }

    public function get_item_schema()
    {
        if ($this->schema) {
            // Since WordPress 5.3, the schema can be cached in the $schema property.
            return $this->schema;
        }

        $this->schema = array(
            // This tells the spec of JSON Schema we are using which is draft 4.
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            // The title property marks the identity of the resource.
            'title'                => 'qm_survey',
            'type'                 => 'object',
            // In JSON Schema you can specify object properties in the properties attribute.
            'properties'           => array(
                'id' => array(
                    'description'  => 'Unique identifier for the object.',
                    'type'         => 'integer',
                    'readonly'     => true,
                ),
                'name' => array(
                    'description'  => 'Unique name for the object.',
                    'type'         => 'string',
                ),
                'questions' => array(
                    'description'  => 'List of questions',
                    'type'         => 'array',
                    'properties'   => array(
                        'items' => array(
                            'type' => 'object',
                            'format' => array(
                                'question' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'answers' => array(
                    'description'  => 'List of answers',
                    'type'         => 'array',
                    'properties'   => array(
                        'items' => array(
                            'type' => 'object',
                            'format' => array(
                                'categorie' => array(
                                    'type' => 'integer',
                                ),
                                'text' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );

        return $this->schema;
    }
}
