<script lang="ts">
    import { register } from 'swiper/element/bundle';
    import HelpIcon from './HelpIcon.svelte';
    register();

    let carousel;
    let resultButton;
    let resultContainer;
    let resultShown = false;
    let answers = {};
    let style = "";

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
        const valeursMaxOccurence = Object.keys(occurences).filter(
            (valeur) => occurences[valeur] === maxOccurence
        );

        return valeursMaxOccurence;
    }

    const handleAnswerClick = (ev) => {
        if (resultShown) {
            resultShown = false;
            resultButton.classList.remove("hidden");
            resultContainer.classList.add("hidden");
        }

        carousel.swiper.slideNext();

        let catId = ev.target.dataset.cat;
        let questId = ev.target.dataset.quest;

        if (answers[questId] != undefined) {
            answers[questId].obj.classList.remove("selected");
        }
        ev.target.classList.add("selected");
        answers[questId] = {
            cat: catId,
            obj: ev.target,
        };
    };

    const revealResult = () => {
        if (resultShown) {
            return;
        }

        resultShown = true;
        resultButton.classList.add("hidden");
        resultContainer.classList.remove("hidden");

        let mosts = trouverValeursPlusPresentes(answers, "cat");

        let text = "Vous êtes:<br/>";

        for (let index = 0; index < mosts.length; index++) {
            const ans = mosts[index];
            let txt = categories.find((obj) => {
                return obj.id == ans;
            }).name;

            if (index < mosts.length - 2) {
                txt += ", ";
            }
            if (index == mosts.length - 2) {
                txt += " et ";
            }

            text += txt;
        }

        resultContainer.innerHTML = text;
    };

    var MyModel = wp.api.models.Post.extend({
        url: function () {
            return (
                wpApiSettings.root +
                "nz-quizmosaic/v1/survey/" +
                (this.get("id") ?? "")
            );
        },
    });

    const urlParams = new URLSearchParams(window.location.search);
    const survey = urlParams.get("survey");
    let categories = [];
    let questions = [];
    let isEdit = false;

    let colors = {
        "--nz-qm-ans-btn-background": "#ffffff",
        "--nz-qm-ans-btn-border": "#d5d9d9",
        "--nz-qm-ans-btn-color": "#0f1111",
        "--nz-qm-ans-btn-hover-background": "#f7fafa",
        "--nz-qm-ans-btn-hover-color": "#0f1111",
        "--nz-qm-ans-btn-selected-color": "#008296",
        "--nz-qm-res-btn-background": "#ffffff",
        "--nz-qm-res-btn-color": "#3c4043",
        "--nz-qm-res-btn-hover-background": "#F6F9FE",
        "--nz-qm-res-btn-hover-color": "#174ea6",
        "--nz-qm-ques-color": "#000000",
        "--nz-qm-res-color": "#000000",
        "--nz-qm-error-color": "#ff0000",
        "--swiper-pagination-bullet-inactive-color": "#007aff",
        "--swiper-pagination-color": "#007aff",
        "--swiper-navigation-color": "#007aff",
    };
    const defaultColors = JSON.parse(JSON.stringify(colors));

    const handleColorChange = () => {
        style = Object.keys(colors)
            .map((key) => [key, colors[key]].join(":"))
            .join(";");
    };

    const load = async () => {
        if (survey !== null && survey !== undefined && survey !== "") {
            var myModel = new MyModel({ id: survey });

            // Récupération d'un élément
            await myModel
                .fetch()
                .done(function () {
                    let data = myModel.toJSON();
                    surveyName = data["name"];
                    isEdit = true;

                    for (const [key, value] of Object.entries(
                        data["categories"]
                    )) {
                        categories = [
                            ...categories,
                            {
                                id: key,
                                name: value,
                            },
                        ];
                    }

                    data["questions"].forEach((question) => {
                        let ans = {};
                        question["answers"].forEach((element) => {
                            ans[element.category] = element.answer;
                        });

                        questions = [
                            ...questions,
                            {
                                id: parseInt(question["id"]),
                                question: question["question"],
                                answers: ans,
                            },
                        ];
                    });

                    if (data["style"] != null) {
                        data["style"].split(";").forEach((element) => {
                            let tmp = element.split(":");
                            colors[tmp[0]] = tmp[1];
                        });
                        handleColorChange();
                    }
                })
                .fail(function (response) {
                    console.error(
                        "Une erreur s'est produite lors de la récupération:",
                        response.responseJSON
                    );
                });
        }
    };

    load();

    let json = null;
    let surveyName = "";

    const handleSubmit = (ev) => {
        let answers = {};
        let quests = [];
        let formatedStyle = Object.keys(colors)
            .map((key) => [key, colors[key]].join(":"))
            .join(";");

        categories.forEach((element) => {
            answers[element.id] = element.name;
        });

        questions.forEach((quest) => {
            let tmp = {
                id: quest.id,
                question: quest.question,
                answers: [],
            };

            for (const [key, value] of Object.entries(quest.answers)) {
                tmp.answers.push({
                    category: key,
                    answer: value,
                });
            }

            quests.push(tmp);
        });

        let formatted = {
            name: surveyName,
            questions: quests,
            categories: answers,
            style: formatedStyle,
        };

        json = JSON.stringify(formatted);

        if (!isEdit) {
            // Création d'un nouvel élément
            var newModel = new MyModel();
            newModel
                .save({ data: json })
                .done(function () {
                    let data = newModel.toJSON();
                    alert("Created survey id: #" + data["data"]["id"]);
                })
                .fail(function (response) {
                    console.error(
                        "Une erreur s'est produite lors de la création:",
                        response.responseJSON
                    );
                });
        } else {
            var myModel = new MyModel({ id: survey });

            myModel
                .save({ data: json })
                .done(function () {
                    let data = myModel.toJSON();
                    alert("Modified survey id: #" + data["data"]["id"]);
                })
                .fail(function (response) {
                    console.error(
                        "Une erreur s'est produite lors de la mise à jour:",
                        response.responseJSON
                    );
                });
        }
    };

    const handleCategoriesAddClick = (ev) => {
        let id;

        if (categories.length == 0) {
            id = 1;
        } else {
            id = parseInt(categories.slice(-1)[0].id) + 1;
        }

        categories = [
            ...categories,
            {
                id: id,
                name: null,
            },
        ];

        questions.forEach((quest) => {
            quest.answers[id] = "";
        });
        questions = questions;
    };

    const handleCategorieChange = (ev) => {
        const index = categories.findIndex(
            (item) => item.id == parseInt(ev.target.dataset.catid)
        );
        categories[index].name = ev.target.value;
    };

    const handleCategorieDeleteClick = (ev) => {
        const index = categories.findIndex(
            (item) => item.id == parseInt(ev.target.dataset.catid)
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
                answers: {},
            },
        ];
    };

    const handleQuestionTextChange = (ev) => {
        const index = questions.findIndex(
            (item) => item.id == parseInt(ev.target.dataset.questid)
        );
        questions[index].question = ev.target.value;
    };

    const handleQuestionAnswerChange = (ev) => {
        var question = questions.find((obj) => {
            return obj.id == parseInt(ev.target.dataset.questid);
        });

        question.answers[parseInt(ev.target.dataset.catid)] = ev.target.value;
        questions = questions;
    };

    const handleQuestionDeleteClick = (ev) => {
        const index = questions.findIndex(
            (item) => item.id == parseInt(ev.target.dataset.questid)
        );

        questions.splice(index, 1);
        questions = questions;
    };
    const handleResetClick = () => {
        colors = defaultColors;
        handleColorChange();
    };
</script>

<div>
    <div class="nz-quizmosaic--questionModule" {style}>
        <swiper-container bind:this={carousel} navigation="true" pagination="true">
            {#each questions as question}
                <swiper-slide>
                    <div class="col">
                        <h1 class="question">{question.question}</h1>
                        <div class="col gap">
                            {#each Object.entries(question.answers) as [cat, ans]}
                                <button
                                    class="answerBtn"
                                    data-cat={cat}
                                    data-quest={question.id}
                                    on:click={handleAnswerClick}>{ans}</button
                                >
                            {/each}
                        </div>
                    </div>
                </swiper-slide>
            {/each}
            <swiper-slide>
                <div class="col gap">
                    <button
                        class="resultBtn"
                        on:click={revealResult}
                        bind:this={resultButton}>Montrer le résultat</button
                    >
                    <span class="result hidden" bind:this={resultContainer} />
                </div>
            </swiper-slide>
        </swiper-container>
    </div>
    <form method="post" on:submit|preventDefault={handleSubmit}>
        <div class="nz-quizmosaic--adminRow">
            <div class="nz-quizmosaic--rowItem nz-quizmosaic--form">
                <input type="hidden" name="json" value={json} />
                <div class="nz-quizmosaic--admin-header">
                    <span>Nom:</span>
                    <input
                        type="text"
                        name="surveyName"
                        minlength="1"
                        maxlength="100"
                        bind:value={surveyName}
                        required
                    />
                    <button type="submit"
                        >{#if isEdit}modifier{:else}créer{/if}</button
                    >
                    <HelpIcon>
                        <h1>Nom</h1>
                        <p>Le nom n'est disponible que dans l'interface d'administration, et vous permet de facilement identifier vos questionnaires</p>
                        <p>100 chars max</p>
                    </HelpIcon>
                </div>
                <div>
                    <div class="nz-quizmosaic--admin-header">
                        <h1>Categories ({categories.length})</h1>
                        <button
                            type="button"
                            class="nz-quizmosaic--admin-addBtn"
                            on:click={handleCategoriesAddClick}>+</button
                        >
                        <HelpIcon>
                            <h1>Catégories</h1>
                            <p>Les categories sont les résultats possible, il sont afficher séparer par une virgule puis par 'et'.</p>
                            <p>100 chars max</p>
                        </HelpIcon>
                    </div>
                    <div class="nz-quizmosaic--admin-container">
                        {#each categories as category (category.id)}
                            <div class="nz-quizmosaic--admin-row">
                                <span>Nom:</span>
                                <input
                                    type="text"
                                    minlength="1"
                                    maxlength="100"
                                    data-catId={category.id}
                                    value={category.name}
                                    required
                                    on:change={handleCategorieChange}
                                /><button
                                    class="nz-quizmosaic--admin-btn"
                                    data-catId={category.id}
                                    on:click={handleCategorieDeleteClick}
                                    >supprimer</button
                                >
                            </div>
                        {/each}
                    </div>
                </div>
                <div>
                    <div class="nz-quizmosaic--admin-header">
                        <h1>Questions ({questions.length})</h1>
                        <button
                            type="button"
                            class="nz-quizmosaic--admin-addBtn"
                            on:click={handleQuestionsAddClick}>+</button
                        >
                        <HelpIcon>
                            <h1>Questions</h1>
                            <p>help</p>
                            <p>255 chars max pour la question et 255 max pour les réponses</p>
                        </HelpIcon>
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
                                        minlength="1"
                                        maxlength="255"
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
                                            <span
                                                >Reponse pour: {category.name ??
                                                    "N/A"}</span
                                            >
                                            <input
                                                type="text"
                                                minlength="1"
                                                maxlength="255"
                                                data-questId={question.id}
                                                data-catId={category.id}
                                                value={question.answers[
                                                    category.id
                                                ] ?? ""}
                                                required
                                                on:change={handleQuestionAnswerChange}
                                            />
                                        </div>
                                    {/each}
                                </div>
                            </div>
                        {/each}
                    </div>
                </div>
            </div>
            <div class="nz-quizmosaic--rowItem nz-quizmosaic--limit">
                <button type="button" on:click={handleResetClick}>reset</button>
                <div class="nz-quizmosaic--adminRow2">
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="ansBtnBack">Answer Btn Background</label>
                        <input
                            type="color"
                            id="ansBtnBack"
                            bind:value={colors["--nz-qm-ans-btn-background"]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="ansBtnBorder">Answer Btn Border</label>
                        <input
                            type="color"
                            id="ansBtnBorder"
                            bind:value={colors["--nz-qm-ans-btn-border"]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="ansBtnColor">Answer Btn Color</label>
                        <input
                            type="color"
                            id="ansBtnColor"
                            bind:value={colors["--nz-qm-ans-btn-color"]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="ansBtnHoverBack"
                            >Answer Btn Hover Back</label
                        >
                        <input
                            type="color"
                            id="ansBtnHoverBack"
                            bind:value={colors[
                                "--nz-qm-ans-btn-hover-background"
                            ]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="ansBtnHoverColor"
                            >Answer Btn Hover Color</label
                        >
                        <input
                            type="color"
                            id="ansBtnHoverColor"
                            bind:value={colors["--nz-qm-ans-btn-hover-color"]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="ansBtnSelect">Answer Btn Selected</label>
                        <input
                            type="color"
                            id="ansBtnSelect"
                            bind:value={colors[
                                "--nz-qm-ans-btn-selected-color"
                            ]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="resBtnBack">Resultat Btn Back</label>
                        <input
                            type="color"
                            id="resBtnBack"
                            bind:value={colors["--nz-qm-res-btn-background"]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="resBtnColor">Resultat Btn Color</label>
                        <input
                            type="color"
                            id="resBtnColor"
                            bind:value={colors["--nz-qm-res-btn-color"]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="resBtnHoverBack"
                            >Resultat Btn Hover Back</label
                        >
                        <input
                            type="color"
                            id="resBtnHoverBack"
                            bind:value={colors[
                                "--nz-qm-res-btn-hover-background"
                            ]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="resBtnHoverColor"
                            >Resultat Btn Hover Color</label
                        >
                        <input
                            type="color"
                            id="resBtnHoverColor"
                            bind:value={colors["--nz-qm-res-btn-hover-color"]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="resColor">Result Color</label>
                        <input
                            type="color"
                            id="resColor"
                            bind:value={colors["--nz-qm-res-color"]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="quesColor">Question Color</label>
                        <input
                            type="color"
                            id="quesColor"
                            bind:value={colors["--nz-qm-ques-color"]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="errorColor">Error Color</label>
                        <input
                            type="color"
                            id="errorColor"
                            bind:value={colors["--nz-qm-error-color"]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="rgbLight50">Pagination Color Inactive</label>
                        <input
                            type="color"
                            id="rgbLight50"
                            bind:value={colors["--swiper-pagination-bullet-inactive-color"]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="rgbLight">Pagination Color</label>
                        <input
                            type="color"
                            id="rgbLight"
                            bind:value={colors["--swiper-pagination-color"]}
                            on:change={handleColorChange}
                        />
                    </div>
                    <div class="nz-quizmosaic--rowItem2">
                        <label for="hexDark">Arrow Color</label>
                        <input
                            type="color"
                            id="hexDark"
                            bind:value={colors["--swiper-navigation-color"]}
                            on:change={handleColorChange}
                        />
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
