<script lang="ts">
    import Game from '../game/Game.svelte';
    let questId = document.getElementById('questionModule').dataset.questionnaire;

    let categories = [];
    let questions = [];
    let style = "";

    var MyModel = Backbone.Model.extend({
        url: function() {
            return wpApiSettings.root + 'nz-quizmosaic/v1/survey/' + this.get('id');
        },
    });

    var myModel = new MyModel({ id: questId });

    let load = myModel.fetch()
        .done(function() {
            let data = myModel.toJSON();
            categories = data['categories'];
            questions = data['questions'];
            style = data['style']
        })
        .fail(function(response) {
            console.error('Une erreur s\'est produite lors de la récupération:', response.responseJSON);
            throw new Error("error");
        });
</script>

{#await load}
	<p class="message">Chargement du questionnaire...</p>
{:then}
    <Game categories={categories} questions={questions} style={style}></Game>
{:catch error}
    {#await error.responseJSON then resp }
    <div>
        <p class="message error">{resp.message}</p>
    </div>
    {/await}
{/await}
