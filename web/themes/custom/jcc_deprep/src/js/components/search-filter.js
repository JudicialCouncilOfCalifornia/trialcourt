(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.searchFilter = {
    attach: function (context) {

      $(".jcc-fieldset-search input").on("keyup", function (e) {
        e.preventDefault();

        $(".jcc-fieldset-search .usa-hint").remove();

        let keyword = $(".jcc-fieldset-search input").val().trim();

        if (keyword) {
          $(".jcc-fieldset-search").append(function () {
              return `
              <span class="usa-hint position-absolute z-top">
                <div class="usa-alert usa-alert--warning usa-alert--no-icon usa-alert--slim margin-top-1">
                  <div class="usa-alert__body">
                    <p class="usa-alert__text">
                      <div class="text-accent-warm-darker">Search <a href="/search?keywords=${keyword}" class="text-no-underline"><span class="usa-tag bg-accent-warm-darker padding-y-05 hover:bg-accent-cool-dark">ALL</span></a> or within:</div>
                      <a href="/search?keywords=${keyword}&f[0]=document_type%3A28" class="text-no-underline"><span class="usa-tag bg-accent-warm-darker padding-y-05 hover:bg-accent-cool-dark">Case Summaries</span></a><br />
                      <a href="/search?keywords=${keyword}&f[0]=document_type%3A26" class="text-no-underline"><span class="usa-tag bg-accent-warm-darker padding-y-05 hover:bg-accent-cool-dark">Publications</span></a><br />
                      <a href="/search?keywords=${keyword}&f[0]=document_type%3A23" class="text-no-underline"><span class="usa-tag bg-accent-warm-darker padding-y-05 hover:bg-accent-cool-dark">Training</span></a><br />
                      <a href="/search?keywords=${keyword}&f[0]=document_type%3A5" class="text-no-underline"><span class="usa-tag bg-accent-warm-darker padding-y-05 hover:bg-accent-cool-dark">Legal and Policy Resources</span></a>
                    </p>
                  </div>
                </div>
              </span>`;
            }
          );
        }

      });

      $(document).on("click", function (event) {
        var $target = $(event.target);
        if(!$target.closest('.jcc-fieldset-search .usa-hint').length &&
          $('.jcc-fieldset-search .usa-hint').is(":visible")) {
          $('.jcc-fieldset-search .usa-hint').hide();
        }
      });

    }
  };
})(jQuery, Drupal);
