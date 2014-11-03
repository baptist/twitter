
<script>




    $(document).ready(function() {


        function loadSelectWidget(_id) {
            $(".widget").css("display", "none");
            $("#" + _id).css("display", "block")
        }

        Array.prototype.unique = function() {
            var a = this.concat();
            for (var i = 0; i < a.length; ++i) {
                for (var j = i + 1; j < a.length; ++j) {
                    if (a[i] === a[j])
                        a.splice(j--, 1);
                }
            }

            return a;
        };

        
        function removeEmpty(val)
        {
            if (!(val instanceof Array))
            {
                return (val === "") ? null : val;
            }

            t = [];
            for (var i = 0; i < val.length; i++)
            {
                if (val[i] !== "")
                    t.push(val[i]);
            }
            return t;
        }

        function split(val) {
            return val.split(/,\s*/);
        }
        function extractLast(term) {
            return split(term).pop();
        }

        $("#select-widget-combo").ready(function() {
            loadSelectWidget($(this).find('option:selected').val());
        });

        $("#select-widget-combo").change(function() {
            loadSelectWidget($(this).find('option:selected').val());
        });



        $('#tagsSelect').multiselect({
            includeSelectAllOption: true,
            numberDisplayed: 1,
            enableFiltering: true,
            maxHeight: 250,
            buttonWidth: 250
        });

        $('#typesSelect').multiselect({
            includeSelectAllOption: true,
            numberDisplayed: 2,
            maxHeight: 150,
            buttonWidth: 250
        });


        var availableKeywords = [
<?php
$keys = $tk->getKeywords(-1);
foreach ($keys as $key)
    echo "'$key',"
    ?>
        ];


        $("#keywordsAuto")
                // don't navigate away from the field on tab when selecting an item
                .bind("keydown", function(event) {
            if (event.keyCode === $.ui.keyCode.TAB &&
                    $(this).data("ui-autocomplete").menu.active) {
                event.preventDefault();
            }
        })
                .autocomplete({
            minLength: 0,
            source: function(request, response) {
                // delegate back to autocomplete, but extract the last term
                response($.ui.autocomplete.filter(
                        availableKeywords, extractLast(request.term)));
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function(event, ui) {
                var terms = split(this.value);
                // remove the current input
                terms.pop();
                // add the selected item
                terms.push(ui.item.value);
                // add placeholder to get the comma-and-space at the end
                terms.push("");
                this.value = terms.join(", ");
                return false;
            },
            open: function() {
                $(this).autocomplete('widget').zIndex(10000);
            }
        });

        $("#cancel_select").click(function(event) {
            $("#select_widget").bPopup().close();
        });

        $("#add_select").click(function(event)
        {
            // select correct widget
            var widget_id = $("#select-widget-combo").find('option:selected').val();
            // select field and value
            var field = $("div#" + widget_id + " > div.widget-main > input[name=field]").val();
            var num_values = parseInt($("div#" + widget_id + " > div.widget-main > input[name=num_values]").val());

            var value;
            if (num_values > 1)
            {
                value = [];
                for (var i = 1; i <= num_values; i++)
                    value.push($("div#" + widget_id).find("[name=value" + i + "]").val());
            }
            else
                value = $("div#" + widget_id).find("[name=value]").val();

            var is_array = ($("div#" + widget_id + " > div.widget-main > input[name=is_array]").val() === "1");
            // make sure value is array
            if (is_array)
            {
                if (!(value instanceof Array))
                    value = split(value);
            }

            // filter out empty values
            value = removeEmpty(value);

            if (value !== null)
            {
                // add to select list and update
                if (typeof window.select_statements[field] != "undefined")
                    window.select_statements[field] = (is_array) ? window.select_statements[field].concat(value).unique() : value;
                else
                    window.select_statements[field] = (is_array) ? value.unique() : value;

                window.refreshSelectList();
            }

            // close popup
            $("#select_widget").bPopup().close();           
        });

    });
</script>



<div style="width:400px;height:170px; background:#FFF; border-radius: 5px; box-shadow: 0px 0px 25px 5px #999">

    <div>
        <div class="widget-title" style="border-top-left-radius: 5px;border-top-right-radius: 5px;">
            select archive(s) based on
            <select name="type" id="select-widget-combo" style="margin-left:20px;" >
                <option value='keyword-widget' selected="true">Keywords</option>
                <option value='tag-widget'>Tags</option>
                <option value='description-widget'>Description</option>
                <option value='type-widget'>Type</option>
            </select>
        </div>

        <div id='tag-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Tags</span>
                <input type="hidden" name="is_array" value="1" />
                <input type="hidden" name="num_values" value="1" />
                <input type="hidden" name="field" value="tags" />
                <select name="value" class="multiselect"  multiple="multiple" id="tagsSelect" style="padding:10px; width:90%">
                    <?php
                    $tags = $tk->getUniformTags(-1);

                    foreach ($tags as $tag)
                        echo "<option value='$tag'>" . ucfirst($tag) . "</option>";
                    ?>

                </select>
            </div>
        </div>

        <div id='keyword-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Keywords</span>
                <input type="hidden" name="is_array" value="1" />
                <input type="hidden" name="field" value="keywords" />
                <input type="hidden" name="num_values" value="1" />
                <input type="text"   name="value" id='keywordsAuto' class="widget-input" style="padding:10px; width:90%"/>
            </div>
        </div>

        <div id='description-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Description</span>
                <input type="hidden" name="is_array" value="0" />
                <input type="hidden" name="num_values" value="1" />
                <input type="hidden" name="field" value="description" />
                <input type="text"   name="value" class="widget-input" style="padding:10px; width:90%"/>
            </div>
        </div>
        <div id='type-widget' class='widget'>

            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Type</span>
                <input type="hidden" name="is_array" value="1" />
                <input type="hidden" name="num_values" value="1" />
                <input type="hidden" name="field" value="type" />
                <select name="value" class="multiselect"  multiple="multiple" id="typesSelect" style="padding:10px; width:90%">
                    <?php
                    $types = array("keyword", "#hashtag", "@user");
                    $i = 1;
                    foreach ($types as $type)
                        echo "<option value='" . $i++ . "'>" . $type . "</option>";
                    ?>
                </select>
            </div>
        </div>
        <button value="Cancel" class="ui-button-default" id="cancel_select"  style="position:absolute;bottom:5px;right:65px">Cancel</button>
        <button value="Save" class="ui-button-primary" id="add_select" style="position:absolute;bottom:5px;right:10px">Add</button>

    </div>
</div>