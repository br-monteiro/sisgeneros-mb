    var beginningDate = $("#beginningDate");
    var endingDate = $("#endingDate");
    var btnPreviousIngredients = $(".btn-previous-ingredients");
    var btnCheckoutMenu = $(".btn-checkout-menu");
    var menuJson = [];
    var itemsMap = [];
    var menuMap = [];
    var currentDay = 1;
    var currentDayObj = {};
    var x = 1;

    initView();

    /**
     * Reverse the Date in portuguese format to english format
     * @param { String } Value
     * @return { String }
     */
    var reverseDate = function rverseDate(value) {
        return value && value.split("-").reverse().join("-");
    };

    beginningDate.on("blur", function () {
        if (validateDate(beginningDate.val())) {
            var day = new Date(reverseDate(beginningDate.val()));
            var nextWeek = new Date(day.getTime() + 7 * 86400000);
            endingDate.val(formatDate(nextWeek));
        }
    });

    $(".btn-next-menu").on("click", function () {
        if (validateDate(endingDate.val())) {
            var dateEnglish = reverseDate(beginningDate.val());
            $.get("cardapio/checkdate/value/" + dateEnglish, function (data) {
                var date = new Date(dateEnglish);
                var result = jsonParse(data);
                if (result.menusId === 0) {
                    for (i = 1; i <= 7; i++) {
                        var day = new Date(date.getTime() + i * 86400000);
                        menuMap.push({
                            "date": formatDate(day),
                            "order": i,
                            "data": []
                        });
                    }

                    hideFormCard("menu");
                    showFormCard("recipes");
                    $("#date").val(getFirstDay());
                } else {
                    window.alert('Esta data já esta sendo usada em outro cardápio.');
                }
            });
        } else {
            window.alert("Data inválida!");
        }
    });

    $(".btn-next-day").on("click", function () {
        if (currentDay < 7) {
            var order = currentDay + 1;
            var day = getObjDay(order);
            if (day) {
                currentDayObj = day;
                currentDay = order;
                $("#date").val(day.date);
            }
        }
    });

    $(".btn-previous-day").on("click", function () {
        if (currentDay > 1) {
            var order = currentDay - 1;
            var day = getObjDay(order);
            if (day) {
                currentDayObj = day;
                currentDay = order;
                $("#date").val(day.date);
            }
        }
    });

    $(".btn-next-recipes").on("click", function () {
        if (validateDate($("#date").val())) {
            $.get("recipespatterns/findRecipeItemsByRecipesId/id/" + $("#recipes").val(), function (data) {
                buildTableIngredients(data);
            });
            hideFormCard("recipes");
            showFormCard("ingredients");
        } else {
            alert("Data inválida!");
        }
    });

    $(".btn-next-ingredients").on("click", function () {
        var items = getValuesIngredients();
        var recipes = {
            "recipes": {
                "meals": {
                    "id": $("#mealsId").val(),
                    "name": $("#mealsId option:selected").text(),
                },
                "recipesPatternsId": $("#recipes").val(),
                "name": $("#recipes option:selected").text(),
                "quantity": $("#quantity_people").val(),
                "items": items
            }
        };
        var day = getObjDay(currentDay);
        day.data.push(recipes);
        console.log(menuMap);

        hideFormCard("ingredients");
        showFormCard("recipes");
        showFormCard("resume-menu");
        buildTableResume();
        cleanFormRecipes();
        $('#row-list').html("");
    });

    $(".btn-previous-ingredients").on("click", function () {
        hideFormCard("ingredients");
        showFormCard("recipes");
    });

    btnCheckoutMenu.on("click", function () {
        if (validateMenus()) {
            $('.loading').show();
            $.ajax({
                type: "POST",
                url: "cardapio/registra/",
                data: {menuMap},
                success: function (data) {
                    var id = jsonParse(data, {}).id;
                    var url = 'cardapio/ver';
                    if (id) {
                        url = 'cardapio/detalhar/id/' + id;
                    }
                    window.location = url;
                }
            });
        } else {
            window.alert('O cardápio deve conter ao menos uma refeição por dia.');
        }
    });
    function getFirstDay() {
        return menuMap.find(function (item) {
            return item.order === 1;
        }).date;
    }

    function getObjDay(order) {
        return menuMap.find(function (item) {
            return item.order === order;
        });
    }

    function formatDate(dateVal) {
        var month = dateVal.getMonth() + 1;
        var day = dateVal.getDate();

        var output =
                (day < 10 ? '0' : '') + day + '-' +
                (month < 10 ? '0' : '') + month + '-' +
                dateVal.getFullYear();

        return output;
    }

    /**
     * Show an element by selector
     * @param { String } selector
     * @returns { Void }
     */
    function showFormCard(selector) {
        $('.' + selector).show();
    }

    /**
     * Hide an element by selector
     * @param { String } selector
     * @returns { Void }
     */
    function hideFormCard(selector) {
        $('.' + selector).hide();
    }

    /**
     * Convert a JSON string to valid Object or Array
     * @param { String } jsonString
     * @param { * } valueDefault
     * @return { Object | Array }
     */
    function jsonParse(jsonString, valueDefault) {
        try {
            return JSON.parse(jsonString);
        } catch (_) {
            return valueDefault || [];
        }
    }

    function intParse(value) {
        return parseInt(value, 10);
    }

    /**
     * Parse the string to float
     * @param { String } value
     * @return { Number }
     */
    function floatParse(value) {
        var valueParsed = parseFloat(value);
        if (!isNaN(valueParsed)) {
            valueParsed = valueParsed.toFixed(3);
        }
        return valueParsed;
    }

    function initView() {
        hideFormCard("recipes");
        hideFormCard("ingredients");
        hideFormCard("resume-menu");
    }

    function cleanFormRecipes() {
        $("#mealsId").val("");
        $("#recipes").val("");
        $("#quantity_people").val("");
    }

    /**
     * Get the elements from DOM according selector
     * @param { String } selector
     * @return { Object }
     */
    function extractElementsPatterns (selector) {
        return $(selector).clone();
    }

    /**
     * Returns all elements from database according Igrendients Id
     * @param { Number } elementId
     * @param { Function } callback
     * @return { Void }
     */
    function fetchBiddingsByIngredientsId (elementId, callback) {
        $.get("licitacao/ingrendientes/id/" + elementId, function (data) {
            if (typeof callback === 'function') {
                callback(jsonParse(data, []));
            }
        });
    }

    function buildTableIngredients(data) {
        var element = extractElementsPatterns('.quantity-ingredients');
        var obj = jsonParse(data, []);

        if (element.length && obj.length) {
            $('#row-list').html("");
            var quantityPeople = intParse($("#quantity_people").val());
            obj.forEach(function (item) {
                var quantity = floatParse(item.quantity);
                var calc = floatParse(quantityPeople * quantity);
                var elementTemp = element.clone();
                elementTemp.find("input[name='id[]']").val(item.id);
                elementTemp.find("input[name='name[]']").val(item.name);
                elementTemp.find("input[name='suggested_quantity[]']").val(calc);
                elementTemp.find("input[name='quantity[]']").val(calc);
                elementTemp.find("select[name='biddings_items_id[]']").attr('data-reference', item.ingredients_id);
                $('#row-list').append(elementTemp);
                $(".delete-row").on('click', function () {
                    $(this).closest('tr').remove();
                });
            });

            $('select.items').each(function(i, value) {
                var reference = value.getAttribute('data-reference');
                if (reference) {
                    fetchBiddingsByIngredientsId(reference, function (result) {
                        result.forEach(function (item) {
                            var option = document.createElement('option');
                            option.value = item.id;
                            option.innerText = item.biddings_number + ' - ' + item.name;
                            value.appendChild(option);
                        });
                    });
                }
            });
        }
    }

    function buildTableResume() {
        var strHTML = "";
        $("#row-menu-resume").append(strHTML);
        var objDay = getObjDay(currentDay);
        $(".title-menu").html("Cardápio do dia " + objDay.date);

        for (i = 0; i < objDay.data.length; i++) {
            strHTML = "<tr>";
            strHTML += "<td>" + objDay.date + "</td>";
            strHTML += "<td>" + objDay.data[i].recipes.meals.name + "</td>";
            strHTML += "<td>" + objDay.data[i].recipes.name + "</td>";
            strHTML += "<td>" + objDay.data[i].recipes.quantity + "</td>";
            strHTML += "</tr>";
        }

        $("#row-menu-resume").append(strHTML);
    }

    function getValuesIngredients() {
        var item = [];

        $("#row-list tr").each(function () {
            var recipe_item = {};
            recipe_item["biddings_items_id"] = $(this).find("select[name='biddings_items_id[]']").children("option:selected").val();
            recipe_item["name"] = $(this).find("input[name='name[]']").val();
            recipe_item["quantity"] = $(this).find("input[name='quantity[]']").val();

            item.push(recipe_item);
        });
        return item;
    }

    /**
     * Teste if the date is DD-MM-AAAA
     * @param { String } date
     * @return { Boolean }
     */
    function validateDate(date) {
        var regexDate = /^(0[1-9]|1\d|2\d|3[01])-(0[1-9]|1[0-2])-(19|20)\d{2}$/;
        return regexDate.test(date);
    }

    function validateRangeDate(valueDate) {
        var valueDate = new Date(valueDate);
        var startDate = new Date(beginningDate.val());
        var endDate = new Date(endingDate.val());
        if (valueDate.getTime() >= startDate.getTime() && valueDate.getTime() <= endDate.getTime()) {
            return true;
        }
        return false;
    }