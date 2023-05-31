<script lang="ts">
    import Carousel from 'svelte-carousel';
    let questId = document.getElementById('questionModule').dataset.questionnaire;

    let surveyName;
    let categories;
    let questions;
    let carousel ;
    let resultButton;
    let resultContainer;
    let resultShown = false;
    let answers = {};

    var MyModel = Backbone.Model.extend({
        url: function() {
            return wpApiSettings.root + 'nz-quizmosaic/v1/survey/' + this.get('id');
        },
    });

    var myModel = new MyModel({ id: questId });

    let load = myModel.fetch()
        .done(function() {
            let data = myModel.toJSON();
            surveyName = data['name'];
            categories = data['categories'];
            questions = data['questions'];
        })
        .fail(function(response) {
            console.error('Une erreur s\'est produite lors de la récupération:', response.responseJSON);
        });

        function trouverValeursPlusPresentes(tableau, attribut) {
            // Compter l'occurrence de chaque valeur
            const occurences = {};
            let maxOccurence = 0;
            
            for (const objet of Object.values(tableau)) {
                const valeur = objet[attribut];
                
                if (occurences[valeur]) {
                occurences[valeur]++;
                } else {
                occurences[valeur] = 1;
                }
                
                if (occurences[valeur] > maxOccurence) {
                maxOccurence = occurences[valeur];
                }
            }
            
            // Filtrer les valeurs ayant l'occurrence maximale
            const valeursMaxOccurence = Object.keys(occurences).filter(valeur => occurences[valeur] === maxOccurence);
            
            return valeursMaxOccurence;
        }

        const handleAnswerClick = (ev) => {
            if (resultShown) {
                resultShown = false;
                resultButton.classList.remove('hidden');
                resultContainer.classList.add('hidden');
            }

            carousel.goToNext();

            let catId = ev.target.dataset.cat;
            let questId = ev.target.dataset.quest;
            
            if (answers[questId] != undefined) {
                answers[questId].obj.classList.remove('selected');
            }
            ev.target.classList.add('selected');
            answers[questId] = {
                cat: catId,
                obj: ev.target,
            };
        }

        const revealResult = () => {
            if (resultShown) {
                return;
            }
            resultShown = true;
            resultButton.classList.add('hidden');
            resultContainer.classList.remove('hidden');

            let mosts = trouverValeursPlusPresentes(answers, 'cat');

            let text = 'Vous êtes:<br/>';

            for (let index = 0; index < mosts.length; index++) {
                const ans = mosts[index];
                let txt = categories[ans];
                
                if (index < mosts.length-2 ) {
                    txt += ', ';
                }
                if (index == mosts.length-2) {
                    txt += ' et ';
                }

                text += txt;
            }

            resultContainer.innerHTML = text;
        }
</script>

{#await load}
	<p>Chargement du questionnaire...</p>
{:then}
    <Carousel bind:this={carousel}>
        {#each questions as question}
            <div class="col">
                <h5 class="question">{question.question}</h5>
                <div class="col gap">
                    {#each question.answers as answer}
                        <button class="answerBtn" data-cat="{answer.category}" data-quest="{question.id}" on:click={handleAnswerClick}>{answer.answer}</button>
                    {/each}
                </div>
            </div>
        {/each}
        <div class="col gap">
            <button class="resultBtn" on:click={revealResult} bind:this={resultButton}>Montrer le résultat</button>
            <span class="result hidden" bind:this={resultContainer}></span>
        </div>
    </Carousel>
{:catch error}
	<p style="color: red">{error.message}</p>
{/await}
