(function ($, Drupal) {
  Drupal.behaviors.jccEmbedGenerator = {
      // TODO : Style
      // TODO : Taxonomy Views
      attach: function (context, settings) {
        var data_count = "10",
          block_width = "150",
          hide_date = "no",
          hide_description = "no",
          taxonomy_id = "",
          taxonomy_name = "",
          data_origin = "https://develop-jcc-newsroom.pantheonsite.io"; //"http://newsroom.lndo.site"

        // DATA-COUNT
        $('<select id="items-select-data-count"></select>').appendTo('#data-count');
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
        $('<select id="items-select-block-width"></select>').appendTo('#block-width');
        $('#items-select-block-width').append(`<option value="" selected disabled hidden>Block Width</option>`);
        $('#items-select-block-width').append(`<option value="150">150</option>`);
        $('#items-select-block-width').append(`<option value="300">300</option>`);
        $('#items-select-block-width').append(`<option value="600">600</option>`);

        $('#items-select-block-width').on('change', function () {
          block_width = this.value;
          generateCode();
        });

        // HIDE-DATE
        $('<label for="hide-date">Hide Date</label><input  id="items-select-data-hide-date" type="checkbox" name="hide-date" value="yes" />').appendTo('#data-hide-date');

        $('#items-select-data-hide-date').on('change', function () {
          if (this.checked) {
            hide_date = this.value;
          } else {
            hide_date = "no";
          }
          generateCode();
        });

        // HIDE-DATE
        $('<label for="hide-description">Hide Description</label><input id="items-select-data-hide-description" type="checkbox" name="hide-description" value="yes" />').appendTo('#data-hide-description');

        $('#items-select-data-hide-description').on('change', function () {
          if (this.checked) {
            hide_description = this.value;
          } else {
            hide_description = "no";
          }
          generateCode();
        });

        // Global elements
        $('<textarea readonly></textarea>').appendTo('.generator_result_wrapper');
        generateCode();

        //Copy to clipboard event
        // TODO: Tooltip to notify of the click
        $('.generator_result_wrapper textarea').on('click', function (e) {
          e.preventDefault();
          this.select();
          document.execCommand("copy");
        })


        function generateCode() {
          var embed_code = '<a class="jcc-newsroom-widget" href="' + data_origin + 'feed/news/32" data-subject="32" data-hide-date="' + hide_date + '" data-hide-description="' + hide_description + '" data-block-width="' + block_width + '" data-origin="' + data_origin + '" data-count="' + data_count + '">Term 32 News</a><script async type="application/javascript" src="' + data_origin + '/themes/custom/jcc_newsroom/feed-widget.js" charset="utf-8"></script>';
          $('.generator_result_wrapper textarea').text(embed_code);
          // Generate preview
          $('.generator_result_preview').html(embed_code);
        }
      }
  };
})(jQuery, Drupal);
