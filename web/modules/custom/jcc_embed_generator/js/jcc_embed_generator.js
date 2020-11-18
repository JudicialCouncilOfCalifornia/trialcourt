(function ($, Drupal) {
  Drupal.behaviors.jccEmbedGenerator = {
    attach: function (context, settings) {
      var data_count = "10",
          block_width = "150",
          hide_date = false,
          hide_description = false,
          taxonomy_id = "",
          taxonomy_name = "";

      // DATA-COUNT
      $('<select id="items-select-data-count"></select>').appendTo('#data-count');
      $('#items-select-data-count').append(`<option value="" selected disabled hidden>Items to show</option>`);
      $('#items-select-data-count').append(`<option value="5">5</option>`);
      $('#items-select-data-count').append(`<option value="10">10</option>`);
      $('#items-select-data-count').append(`<option value="25">25</option>`);
      $('#items-select-data-count').append(`<option value="100">100</option>`);

      $('#items-select-data-count').on('change', function() {
        data_count = this.value;
        generateCode();
      });

      // BLOCK-WIDTH
      $('<select id="items-select-block-width"></select>').appendTo('#block-width');
      $('#items-select-block-width').append(`<option value="" selected disabled hidden>Block Width</option>`);
      $('#items-select-block-width').append(`<option value="150">150</option>`);
      $('#items-select-block-width').append(`<option value="300">300</option>`);
      $('#items-select-block-width').append(`<option value="600">600</option>`);

      $('#items-select-block-width').on('change', function() {
        block_width = this.value;
        generateCode();
      });

      // Global elements
      $('<textarea readonly></textarea>').appendTo('.generator_result_wrapper');
      generateCode();

      //Copy to clipboard event
      // TODO: Tooltip to notify of the click
      $('.generator_result_wrapper textarea').on('click', function(e) {
        e.preventDefault();
        this.select();
        document.execCommand("copy");
      })


      function generateCode(){
        var embed_code = '<a class="jcc-newsroom-widget" href="https://newsroom.courts.ca.gov/search?f%5B0%5D=tags%3A104%22  data-count=”' + data_count + '” data-subject="104" block-width="' + block_width + '" data-origin="https://newsroom.courts.ca.gov"Read> more news about Judicial Ethics Opinions</a><script async type="application/javascript" src="https://newsroom.courts.ca.gov/themes/custom/jcc_newsroom/feed-widget.js%22  charset="utf-8"></script>';
        $('.generator_result_wrapper textarea').text(embed_code);
      }
    }
  };
})(jQuery, Drupal);
