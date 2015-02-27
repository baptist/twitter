
<script>



    $(document).ready(function() {

        $("#filter-widget-combo").ready(function() {
            loadFilterWidget($(this).find('option:selected').val());
        });

        $("#filter-widget-combo").change(function() {
            loadFilterWidget($(this).find('option:selected').val());
        });

        function loadFilterWidget(_id) {
            $(".widget").css("display", "none");
            $("#" + _id).css("display", "block")
        }

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



        $('#fieldsSelect').multiselect({
            includeSelectAllOption: true,
            numberDisplayed: 2,
            maxHeight: 250,
            buttonWidth: 250
        });

        $(".radioset").buttonset();
      
        $("#to").datepicker({dateFormat: 'dd/mm/yy'});
        $("#from").datepicker({dateFormat: 'dd/mm/yy'});

        $("#cancel_filter").click(function(event) {
            $("#filter_widget").bPopup().close();
        });

        $(".radioset").change(function() {
            $(this).find("[type=hidden]").val($(this).find(":radio:checked + label").text());
        });



        $("#add_filter").click(function(event)
        {
            // select correct widget
            var widget_id = $("#filter-widget-combo").find('option:selected').val();
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
                if (typeof window.filter_statements[field] != "undefined")
                    window.filter_statements[field] = (is_array) ? window.filter_statements[field].concat(value).unique() : value;
                else
                    window.filter_statements[field] = (is_array) ? value.unique() : value;

                window.refreshFilterList();
            }

            // close popup
            $("#filter_widget").bPopup().close();

        });

    });
</script>



<div style="width:400px;height:170px; background:#FFF; border-radius: 5px; box-shadow: 0px 0px 25px 5px #999">

    <div>
        <div class="widget-title" style="border-top-left-radius: 5px;border-top-right-radius: 5px;">
            filter archive(s) based on
            <select name="type" id="filter-widget-combo" style="margin-left:20px;" >
                <option value='fields-widget' selected="true">Fields</option>
                <option value='from-user-widget'>From User</option>
                <option value='dates-widget'>Dates</option>
                <option value='no-retweets-widget'>Exclude Retweets</option>
                <option value='no-mentions-widget'>Exclude Mentions</option>
                <option value='include-reactions-widget'>Include Reactions</option>
                <option value='num-retweets-favorites-widget'>Num Retweets/Favorites</option>
                <option value='limit-widget'>Tweet Limit</option>
            </select>
        </div>

        <div id='fields-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Fields</span>
                <input type="hidden" name="is_array" value="1" />
                <input type="hidden" name="num_values" value="1" />
                <input type="hidden" name="field" value="fields" />
                <select name="value" class="multiselect"  multiple="multiple" id="fieldsSelect" style="padding:10px; width:90%">
                    <?php

                    $fields = array_merge($tweet_fields, $optional_tweet_fields);

                    foreach ($fields as $field)
                        echo "<option value='$field'>" . ucfirst($field) . "</option>";
                    ?>

                </select>
            </div>
        </div>
        
        <div id='from-user-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>From User</span>
                <input type="hidden" name="is_array" value="0" />
                <input type="hidden" name="num_values" value="1" />
                <input type="hidden" name="field" value="from_user" />
                <input type="text"   name="value" class="widget-input" style="padding:10px; width:90%"/>
            </div>
        </div>

        <div id='dates-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Dates</span>
                <input type="hidden" name="is_array" value="0" />
                <input type="hidden" name="num_values" value="2" />
                <input type="hidden" name="field" value="dates" />
                From <input type="text" name="value1" id="from" value="" style="padding:10px; width:40%"/> to <input type="text" name="value2" id="to" value="" style="padding:10px; width:40%"/> 
            </div>
        </div>

        <div id='no-retweets-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Exclude Retweets</span>
                <input type="hidden" name="is_array" value="0" />
                <input type="hidden" name="num_values" value="1" />
                <input type="hidden" name="field" value="no retweets" />

                <div style="display:block;position:relative;top:10px;left:10px" class="radioset">
                    <input type="hidden" name="value" value="Yes" />
                    <input type="radio" id="radio1" name="radio1" checked="checked"/><label for="radio1" style="font-size:150%; padding:6px">Yes</label>
                    <input type="radio" id="radio2" name="radio1"/><label for="radio2" style="font-size:150%; padding:6px">No</label>
                </div>
            </div>
        </div>

        <div id='no-mentions-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Exclude Mentions</span>
                <input type="hidden" name="is_array" value="0" />
                <input type="hidden" name="num_values" value="1" />
                <input type="hidden" name="field" value="no mentions" />

                <div style="display:block;position:relative;top:10px;left:10px" class="radioset">
                    <input type="hidden" name="value" value="Yes" />
                    <input type="radio" id="radio3" name="radio2" checked="checked"/><label for="radio3" style="font-size:150%; padding:6px">Yes</label>
                    <input type="radio" id="radio4" name="radio2"/><label for="radio4" style="font-size:150%; padding:6px">No</label>
                </div>
            </div>
        </div>

        <div id='include-reactions-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Include Reactions</span>
                <input type="hidden" name="is_array" value="0" />
                <input type="hidden" name="num_values" value="1" />
                <input type="hidden" name="field" value="include reactions" />

                <div style="display:block;position:relative;top:10px;left:10px" class="radioset">
                    <input type="hidden" name="value" value="Yes" />
                    <input type="radio" id="radio5" name="radio3" checked="checked"/><label for="radio5" style="font-size:150%; padding:6px">Yes</label>
                    <input type="radio" id="radio6" name="radio3"/><label for="radio6" style="font-size:150%; padding:6px">No</label>
                </div>
            </div>
        </div>

        <div id='num-retweets-favorites-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text" style="display:inline-block"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Num Retweets</span>
                <span class="widget-text" style="display:inline-block;margin-left:15%"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Num Favorites</span>
                <br/>
                <input type="hidden" name="is_array" value="0" />
                <input type="hidden" name="num_values" value="5" />
                <input type="hidden" name="field" value="num retweets/favorites" />

                <select name="value1" class="jq-select" style="font-size:150%" >
                    <option value='=' selected="true">=</option>
                    <option value='>'>></option>
                    <option value='>='>>=</option>
                    <option value='<'><</option>
                    <option value='<='><=</option>                    
                </select>
                <input type="text" name="value2" style="padding:5px; width:20%"/>

                <div style="display:inline-block;position:relative;top:-2px"  class="radioset">
                    <input type="hidden" name="value3" value="And" />
                    <input type="radio" id="radio7" name="radio4" checked="checked"/><label for="radio7" style="font-size:110%; padding:4px">And</label>
                    <input type="radio" id="radio8" name="radio4"/><label for="radio8" style="font-size:110%; padding:4px">Or</label>
                </div>
                <select name="value4" class="jq-select" style="font-size:150%;" >
                    <option value='=' selected="true">=</option>
                    <option value='>'>></option>
                    <option value='>='>>=</option>
                    <option value='<'><</option>
                    <option value='<='><=</option>                    
                </select>
                <input type="text" name="value5" style="position:relative;z-index:100000;padding:5px; width:20%"/>
            </div>
        </div>

        <div id='limit-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Limit</span>
                <input type="hidden" name="is_array" value="0" />
                <input type="hidden" name="num_values" value="1" />
                <input type="hidden" name="field" value="limit" />
                <input type="text"   name="value" class="widget-input" style="padding:10px; width:90%"/>
            </div>
        </div>
    </div>

    <button value="Cancel" class="ui-button-default" id="cancel_filter"  style="position:absolute;bottom:5px;right:65px">Cancel</button>
    <button value="Save" class="ui-button-primary" id="add_filter" style="position:absolute;bottom:5px;right:10px">Add</button>

</div>
</div>