
<script>



    $(document).ready(function() {

        $("#analyze-widget-combo").ready(function() {
            loadFilterWidget($(this).find('option:selected').val());
        });

        $("#analyze-widget-combo").change(function() {
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

        $('#tweetSelect').multiselect({
            includeSelectAllOption: true,
            numberDisplayed: 1,
            maxHeight: 250,
            buttonWidth: 210
        });
        $('#userSelect').multiselect({
            includeSelectAllOption: true,
            numberDisplayed: 1,
            maxHeight: 250,
            buttonWidth: 210
        });

        $('#hashtagSelect').multiselect({
            includeSelectAllOption: true,
            numberDisplayed: 1,
            maxHeight: 250,
            buttonWidth: 210
        });
        
        $('#sortSelect').multiselect({
            includeSelectAllOption: true,
            numberDisplayed: 1,
            maxHeight: 250,
            buttonWidth: 210
        });


        $(".radioset").change(function() {
            $(this).find("[type=hidden]").val($(this).find(":radio:checked + label").text());
        });

        $("#cancel_analyze").click(function(event) {
            $("#analyze_widget").bPopup().close();
        });

        $("#add_analyze").click(function(event)
        {
            // select correct widget
            var widget_id = $("#analyze-widget-combo").find('option:selected').val();
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
                if (typeof window.analyze_statements[field] != "undefined")
                    window.analyze_statements[field] = (is_array) ? window.analyze_statements[field].concat(value).unique() : value;
                else
                    window.analyze_statements[field] = (is_array) ? value.unique() : value;

                window.refreshAnalyzeList();
            }

            // close popup
            $("#analyze_widget").bPopup().close();

        });

    });
</script>



<div style="width:400px;height:250px; background:#FFF; border-radius: 5px; box-shadow: 0px 0px 25px 5px #999">

    <div>
        <div class="widget-title" style="border-top-left-radius: 5px;border-top-right-radius: 5px;">
            analyze archive(s) based on
            <select name="type" id="analyze-widget-combo" style="margin-left:20px;" >                
                <option value='tweet-stats-widget' selected="true">Tweet Statistics</option>
                <option value='user-stats-widget'>User Statistics</option>                
                <option value='hashtag-stats-widget'>Hashtag Statistics</option>
                <option value='url-stats-widget'>URL Statistics</option>
                <option value='from-to-relation-widget'>From-To Relations</option>
                <option value='unique-users-widget'>Unique Users</option>
                <option value='sort-by-widget'>Sort By</option>
            </select>
        </div>

        <div id='tweet-stats-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Tweet Statistics</span>
                <input type="hidden" name="is_array" value="0" />
                <input type="hidden" name="num_values" value="2" />
                <input type="hidden" name="field" value="tweet statistics" />
                <div style="display:block;position:relative;padding:15px" class="radioset">
                    <input type="hidden" name="value1" value="Overall" />
                    <input type="radio" id="radio10" name="radio10" checked="checked"/><label for="radio10" style="font-size:110%; padding:6px">Overall</label>
                    <input type="radio" id="radio11" name="radio10"/><label for="radio11" style="font-size:110%; padding:6px">Year</label>
                    <input type="radio" id="radio12" name="radio10"/><label for="radio12" style="font-size:110%; padding:6px">Month</label>
                    <input type="radio" id="radio13" name="radio10"/><label for="radio13" style="font-size:110%; padding:6px">Day</label>
                    <input type="radio" id="radio14" name="radio10"/><label for="radio14" style="font-size:110%; padding:6px">Hour</label>
                </div>

                <div style="padding:7px 15px">
                    <select name="value2" class="multiselect"  multiple="multiple" id="tweetSelect">
                        <?php
                        $fields = array("# tweets", "# tweets with link", "# tweets with hashtag", "# retweets", "# replies");

                        foreach ($fields as $field)
                            echo "<option value='$field'>" . ucfirst($field) . "</option>";
                        ?>

                    </select>
                </div>
            </div>
        </div>

        <div id='user-stats-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>User Statistics</span>
                <input type="hidden" name="is_array" value="0" />
                <input type="hidden" name="num_values" value="2" />
                <input type="hidden" name="field" value="user statistics" />
                <div style="display:block;position:relative;padding:15px" class="radioset">
                    <input type="hidden" name="value1" value="Overall" />
                    <input type="radio" id="radio15" name="radio11" checked="checked"/><label for="radio15" style="font-size:110%; padding:6px">Overall</label>
                    <input type="radio" id="radio16" name="radio11"/><label for="radio16" style="font-size:110%; padding:6px">Individual</label>
                </div>

                <div style="padding:7px 15px">
                    <select name="value2" class="multiselect"  multiple="multiple" id="userSelect">
                        <?php
                        $fields = array("screen name", "name", "followers", "# tweets sent", "# retweets sent", "# replies sent", "# mentions sent", "# retweets received", "# replies received", "# mentions received");

                        foreach ($fields as $field)
                            echo "<option value='$field'>" . ucfirst($field) . "</option>";
                        ?>

                    </select>
                </div>
            </div>
        </div>

        <div id='hashtag-stats-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Hashtag Statistics</span>
                <input type="hidden" name="is_array" value="0" />
                <input type="hidden" name="num_values" value="2" />
                <input type="hidden" name="field" value="hashtag statistics" />
                <div style="display:block;position:relative;padding:15px" class="radioset">
                    <input type="hidden" name="value1" value="Overall" />
                    <input type="radio" id="radio17" name="radio12" checked="checked"/><label for="radio17" style="font-size:110%; padding:6px">Overall</label>
                    <input type="radio" id="radio18" name="radio12"/><label for="radio18" style="font-size:110%; padding:6px">Individual</label>
                </div>

                <div style="padding:7px 15px">
                    <select name="value2" class="multiselect"  multiple="multiple" id="hashtagSelect">
                        <?php
                        $fields = array("# tweets", "# distinct users");

                        foreach ($fields as $field)
                            echo "<option value='$field'>" . ucfirst($field) . "</option>";
                        ?>

                    </select>
                </div>
            </div>
        </div>
        
        <div id='from-to-relation-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>From-To Relations</span>
                <input type="hidden" name="is_array" value="0" />
                <input type="hidden" name="num_values" value="1" />
                <input type="hidden" name="field" value="from-to relations" />
                <div style="display:block;position:relative;top:10px;left:10px" class="radioset">
                    <input type="hidden" name="value" value="Yes" />
                    <input type="radio" id="radio21" name="radio21" checked="checked"/><label for="radio21" style="font-size:150%; padding:6px">Yes</label>
                    <input type="radio" id="radio22" name="radio21"/><label for="radio22" style="font-size:150%; padding:6px">No</label>
                </div>
            </div>
        </div>
        
        <div id='unique-users-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Unique Users</span>
                <input type="hidden" name="is_array" value="0" />
                <input type="hidden" name="num_values" value="1" />
                <input type="hidden" name="field" value="unique users" />
                <div style="display:block;position:relative;top:10px;left:10px" class="radioset">
                    <input type="hidden" name="value" value="Yes" />
                    <input type="radio" id="radio23" name="radio23" checked="checked"/><label for="radio23" style="font-size:150%; padding:6px">Yes</label>
                    <input type="radio" id="radio24" name="radio23"/><label for="radio24" style="font-size:150%; padding:6px">No</label>
                </div>
            </div>
        </div>

        <div id='sort-by-widget' class='widget'>
            <div class="widget-main">
                <span class="widget-text"><img src="resources/icons/icons_0039_Next-Track-small-grey.png" alt=""/>Sort By</span>
                <input type="hidden" name="is_array" value="0" />
                <input type="hidden" name="num_values" value="2" />
                <input type="hidden" name="field" value="hashtag statistics" />


                <div style="padding:15px; display:inline-block">
                    <select name="value1" class="multiselect"  multiple="multiple" id="sortSelect">
                        <?php
                        $fields = $tweet_fields;
                        foreach ($fields as $field)
                            echo "<option value='$field'>" . ucfirst($field) . "</option>";
                        ?>

                    </select>
                </div>

                <div style="display:inline-block;position:relative;padding:15px" class="radioset">
                    <input type="hidden2" name="value" value="Ascending" />
                    <input type="radio" id="radio35" name="radio35" checked="checked"/><label for="radio35" style="font-size:110%; padding:6px">Ascending</label>
                    <input type="radio" id="radio36" name="radio35"/><label for="radio36" style="font-size:110%; padding:6px">Descending</label>
                </div>
            </div>
        </div>

        <button value="Cancel" class="ui-button-default" id="cancel_analyze"  style="position:absolute;bottom:5px;right:65px">Cancel</button>
        <button value="Save" class="ui-button-primary" id="add_analyze" style="position:absolute;bottom:5px;right:10px">Add</button>

    </div>
</div>