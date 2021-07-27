(function ($, Drupal) {
  Drupal.behaviors.jccEmbedGenerator = {
      // TODO : Style
      // TODO : Remove select tag from available tags in autocomplete?
      attach: function (context, settings) {
        var data_count = "10",
          block_width = "150",
          hide_thumbnail = "no",
          hide_date = "no",
          hide_description = "no",
          selected_taxonomy_ids = [],
          selected_taxonomy_names = [],
          taxonomy_term_list = [],
          taxonomy_term_ids = [],
          data_origin="https://newsroom.courts.ca.gov";

        // DATA-COUNT
        $('<label for="data-count">Choose the number of items to display</label><select class="usa-select" name="data-count" id="items-select-data-count"></select>').appendTo('#data-count');
        $('#items-select-data-count', context).append(`<option value="" selected disabled hidden>Items to show</option>`);
        $('#items-select-data-count', context).append(`<option value="5">5</option>`);
        $('#items-select-data-count', context).append(`<option value="10">10</option>`);
        $('#items-select-data-count', context).append(`<option value="25">25</option>`);
        $('#items-select-data-count', context).append(`<option value="100">100</option>`);

        $('#items-select-data-count').on('change', function () {
          data_count = this.value;
          generateCode();
        });

        // BLOCK-WIDTH
        $('<select class="usa-select" id="items-select-block-width"></select>').appendTo('#block-width');
        $('#items-select-block-width').append(`<option value="" selected disabled hidden>Block Width</option>`);
        $('#items-select-block-width').append(`<option value="150">150</option>`);
        $('#items-select-block-width').append(`<option value="300">300</option>`);
        $('#items-select-block-width').append(`<option value="600">600</option>`);

        $('#items-select-block-width').on('change', function () {
          block_width = this.value;
          generateCode();
        });

        // HIDE-THUMBNAIL
        $('<input class="" id="items-select-data-hide-thumbnail" type="checkbox" name="hide-thumbnail" value="yes" /><label for="hide-thumbnail">Hide Thumbnail</label>').appendTo('#data-hide-thumbnail');

        $('#items-select-data-hide-thumbnail').on('change', function () {
          if (this.checked) {
            hide_thumbnail = this.value;
          } else {
            hide_thumbnail = "no";
          }
          generateCode();
        });

        // HIDE-DATE
        $('<input class="" id="items-select-data-hide-date" type="checkbox" name="hide-date" value="yes" /><label for="hide-date">Hide Date</label>').appendTo('#data-hide-date');

        $('#items-select-data-hide-date').on('change', function () {
          if (this.checked) {
            hide_date = this.value;
          } else {
            hide_date = "no";
          }
          generateCode();
        });

        // HIDE-DESCRIPTION
        $('<input id="items-select-data-hide-description" type="checkbox" name="hide-description" value="yes" /><label for="hide-description" >Hide Description</label>').appendTo('#data-hide-description');

        $('#items-select-data-hide-description').on('change', function () {
          if (this.checked) {
            hide_description = this.value;
          } else {
            hide_description = "no";
          }
          generateCode();
        });

        // Global elements
        $('<label>Copy the embed code</label><textarea readonly></textarea><div class="message">Copied to clipboard</div>').appendTo('.generator_result_wrapper');
        $('<form autocomplete="off"><div class="jcc-autocomplete" style="width:300px;"><label for="tag">Select the topics you want to embed</label><input id="tag-selector" type="text" name="tag" placeholder="Tag"></div></form>').appendTo('#term-selector');
        initAutocomplete();
        generateCode();
        let searchParams = new URLSearchParams(window.location.search);
        if(searchParams.has('tid')){
          addTag('', searchParams.get('tid'));
        }

        //Copy to clipboard event
        $('.generator_result_wrapper textarea').on('click', function (e) {
          e.preventDefault();
          this.select();
          document.execCommand("copy");
          $('.generator_result_wrapper .message').show().fadeOut(1500, 'swing');
        });

        // Gather data from html
        function initAutocomplete(){
          $('.taxonomy-term').each(function(){
            taxonomy_term_list.push($(this).html());
            taxonomy_term_ids.push($(this).attr('data-tid'));
          });
          jccAutocomplete(document.getElementById("tag-selector"), taxonomy_term_list, taxonomy_term_ids);
        }

        // Autocomplete functionality
        function jccAutocomplete(inp, arrTerms, arrIds) {
          var currentFocus;
          inp.addEventListener("input", function(e) {
            var a, b, i, val = this.value;
            closeAllLists();
            if (!val) { return false;}
            currentFocus = -1;
            a = document.createElement("DIV");
            a.setAttribute("id", this.id + "autocomplete-list");
            a.setAttribute("class", "jcc-autocomplete-items");
            this.parentNode.appendChild(a);
            for (i = 0; i < arrTerms.length; i++) {
              /*check if the item starts with the same letters as the text field value:*/
              if (arrTerms[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
                b = document.createElement("DIV");
                b.innerHTML = "<strong>" + arrTerms[i].substr(0, val.length) + "</strong>";
                b.innerHTML += arrTerms[i].substr(val.length);
                b.innerHTML += "<input type='hidden' value='" + arrTerms[i] + "'>";
                b.addEventListener("click", function(e) {
                  inp.value = '';
                  addTag(this.getElementsByTagName("input")[0].value);
                  closeAllLists();
                });
                a.appendChild(b);
              }
            }
          });
          /*execute a function presses a key on the keyboard:*/
          inp.addEventListener("keydown", function(e) {
            var x = document.getElementById(this.id + "autocomplete-list");
            if (x) x = x.getElementsByTagName("div");
            if (e.keyCode == 40) { //down
              currentFocus++;
              addActive(x);
            } else if (e.keyCode == 38) { //up
              currentFocus--;
              addActive(x);
            } else if (e.keyCode == 13) { //enter
              e.preventDefault();
              if (currentFocus > -1) {
                if (x) x[currentFocus].click();
              }
            }
          });
          function addActive(x) {
            if (!x) return false;
            removeActive(x);
            if (currentFocus >= x.length) currentFocus = 0;
            if (currentFocus < 0) currentFocus = (x.length - 1);
            x[currentFocus].classList.add("jcc-autocomplete-active");
          }
          function removeActive(x) {
            for (var i = 0; i < x.length; i++) {
              x[i].classList.remove("jcc-autocomplete-active");
            }
          }
          function closeAllLists(elmnt) {
            var x = document.getElementsByClassName("jcc-autocomplete-items");
            for (var i = 0; i < x.length; i++) {
              if (elmnt != x[i] && elmnt != inp) {
                x[i].parentNode.removeChild(x[i]);
              }
            }
          }
          /*execute a function when someone clicks in the document:*/
          document.addEventListener("click", function (e) {
            closeAllLists(e.target);
          });
        }

        function removeTag(element){
          selected_taxonomy_ids.splice( $.inArray(element.attr('data-id'),selected_taxonomy_ids) ,1 );
          selected_taxonomy_names.splice( $.inArray(element.attr('data-name'),selected_taxonomy_names) ,1 );
          $('.selected-tag[data-id="' + element.attr('data-id') + '"]').remove();
          generateCode();
        }

        function addTag(name = '', id = ''){
          let lookupError = false;
          if(name != '') {
            var termName = name;
            var termIdKey = Object.keys(taxonomy_term_list).find(key => taxonomy_term_list[key] === termName);
            var termId = taxonomy_term_ids[termIdKey];
            if(termIdKey == undefined){lookupError = true;}
          } else {
            var termId = id;
            var termNameKey = Object.keys(taxonomy_term_ids).find(key => taxonomy_term_ids[key] === termId);
            var termName = taxonomy_term_list[termNameKey];
            if(termNameKey == undefined){lookupError = true;}
          }
          if (!lookupError) {
            $('<div class="selected-tag" data-id="' + termId + '">' + termName + '<span class="remove-term" data-id="' + termId + '" data-name="' + termName + '">X</span></div>').appendTo($('#selected-tags'));
            $('.remove-term[data-id="' + termId + '"]').click(function () {
              removeTag($(this));
            });
            selected_taxonomy_names.push(termName);
            selected_taxonomy_ids.push(termId);
            generateCode();
          }
        }

        // Generate code snippet / Refresh preview window
        function generateCode() {
          if (selected_taxonomy_ids.length > 0) {
            let generatedTermsId = selected_taxonomy_ids.join('+');
            let generatedTermsName = selected_taxonomy_names.join(', ');
            var embed_code = '<a class="jcc-newsroom-widget" href="' + data_origin + '/feed/news/' + generatedTermsId + '" data-subject="' + generatedTermsId + '" data-hide-thumbnail="' + hide_thumbnail + '" data-hide-date="' + hide_date + '" data-hide-description="' + hide_description + '" data-block-width="' + block_width + '" data-origin="' + data_origin + '" data-count="' + data_count + '">' + generatedTermsName + '</a><script async type="application/javascript" src="' + data_origin + '/themes/custom/jcc_newsroom/feed-widget.js" charset="utf-8"></script>';
            $('.generator_result_wrapper textarea').text(embed_code);
            // Generate preview
            $('.generator_result_preview').html('<label>Preview widget</label>' + embed_code);
          } else {
            $('.generator_result_wrapper textarea').text('Please select a tag.');
            $('.generator_result_preview').html('');
          }
        }
      }
  };
})(jQuery, Drupal);
