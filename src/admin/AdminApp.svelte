<script lang="ts">
    let categories = [];
    let questions = [];
    let json = null;
    let surveyName = '';

    const handleSubmit = (ev) => {
        let answers = {};

        categories.forEach((element) => {
            answers[element.id] = element.name;
        });

        let formatted = {
            name: surveyName,
            questions: questions,
            categories: answers,
        };

        json = JSON.stringify(formatted);

        var MyModel = wp.api.models.Post.extend({
            url: function() {
                return wpApiSettings.root + 'nz-quizmosaic/v1/survey/' + (this.get('id') ?? '');
            },
        });

        // Création d'un nouvel élément
        var newModel = new MyModel();
        newModel.save({ data: json })
            .done(function() {
                let data = newModel.toJSON();
                alert('Created survey id: #'+data['data']['id']);
            })
            .fail(function(response) {
                console.error('Une erreur s\'est produite lors de la création:', response.responseJSON);
            });
    };

    const handleCategoriesAddClick = (ev) => {
        let id;

        if (categories.length == 0) {
            id = 1;
        } else {
            id = categories.slice(-1)[0].id + 1;
        }

        categories = [
            ...categories,
            {
                id: id,
                name: null,
            },
        ];
    };

    const handleCategorieChange = (ev) => {
        const index = categories.findIndex(
            (item) => item.id == ev.target.dataset.catid
        );
        categories[index].name = ev.target.value;
    };

    const handleCategorieDeleteClick = (ev) => {
        const index = categories.findIndex(
            (item) => item.id == ev.target.dataset.catid
        );

        categories.splice(index, 1);
        categories = categories;
    };

    const handleQuestionsAddClick = (ev) => {
        let id;

        if (questions.length == 0) {
            id = 1;
        } else {
            id = questions.slice(-1)[0].id + 1;
        }

        questions = [
            ...questions,
            {
                id: id,
                question: null,
                answers: [],
            },
        ];
    };

    const handleQuestionTextChange = (ev) => {
        const index = questions.findIndex(
            (item) => item.id == ev.target.dataset.questid
        );
        questions[index].question = ev.target.value;
    };

    const handleQuestionAnswerChange = (ev) => {
        var question = questions.find((obj) => {
            return obj.id == ev.target.dataset.questid;
        });

        var answer = question.answers.find((obj) => {
            return obj.category == ev.target.dataset.catid;
        });

        if (answer === undefined) {
            answer = {
                category: ev.target.dataset.catid,
                answer: ev.target.value,
            };

            question.answers.push(answer);
        } else {
            answer.answer = ev.target.value;
        }
    };

    const handleQuestionDeleteClick = (ev) => {
        const index = questions.findIndex(
            (item) => item.id == ev.target.dataset.questid
        );

        questions.splice(index, 1);
        questions = questions;
    };
</script>

<form method="post" on:submit|preventDefault={handleSubmit}>
    <input type="hidden" name="json" value="{json}">
    <div class="nz-quizmosaic--admin-header">
        <span>Nom:</span>
        <input type="text" name="surveyName" bind:value="{surveyName}" required>
        <button type="submit">créer</button>
    </div>
    <section>
        <div class="nz-quizmosaic--admin-header">
            <h1>Categories</h1>
            <button
                type="button"
                class="nz-quizmosaic--admin-addBtn"
                on:click={handleCategoriesAddClick}>+</button
            >
        </div>
        <div class="nz-quizmosaic--admin-container">
            {#each categories as category (category.id)}
                <div class="nz-quizmosaic--admin-row">
                    <span>Nom:</span>
                    <input
                        type="text"
                        data-catId={category.id}
                        value={category.name}
                        required
                        on:change={handleCategorieChange}
                    /><button
                        class="nz-quizmosaic--admin-btn"
                        data-catId={category.id}
                        on:click={handleCategorieDeleteClick}>supprimer</button
                    >
                </div>
            {/each}
        </div>
    </section>
    <section>
        <div class="nz-quizmosaic--admin-header">
            <h1>Questions</h1>
            <button
                type="button"
                class="nz-quizmosaic--admin-addBtn"
                on:click={handleQuestionsAddClick}>+</button
            >
        </div>
        <div
            class="nz-quizmosaic--admin-container nz-quizmosaic--admin-gap-2"
        >
            {#each questions as question (question.id)}
                <div>
                    <div class="nz-quizmosaic--admin-row">
                        <span>Question:</span>
                        <input
                            type="text"
                            data-questId={question.id}
                            value={question.question}
                            required
                            on:change={handleQuestionTextChange}
                        />
                        <button
                            type="button"
                            class="nz-quizmosaic--admin-btn"
                            data-questId={question.id}
                            on:click={handleQuestionDeleteClick}
                            >supprimer</button
                        >
                    </div>
                    <div
                        class="nz-quizmosaic--admin-container nz-quizmosaic--admin-mt"
                    >
                        {#each categories as category (category.id)}
                            <div>
                                <span>Reponse pour: {category.name ?? "N/A"}</span>
                                <input
                                    type="text"
                                    data-questId={question.id}
                                    data-catId={category.id}
                                    value={question.answers[category.id] ?? ""}
                                    required
                                    on:change={handleQuestionAnswerChange}
                                />
                            </div>
                        {/each}
                    </div>
                </div>
            {/each}
        </div>
    </section>
</form>
