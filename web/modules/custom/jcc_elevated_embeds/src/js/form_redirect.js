(function ($, Drupal) {

  Drupal.behaviors.emptyKeywordRedirect = {
     attach: function (context, settings) {
      $('#court-search-officer', context).once('emptyKeywordRedirect').on('submit', function (e) {
        const $keywordInput = $(this).find('input[name="keyword"]');
        const keyword = $keywordInput.val();
         if (keyword !== undefined && keyword.trim() === '') {
          e.preventDefault();
          window.location.href = '/admin/content/jcc-officer';
        }
      });
    }
  };
     
  Drupal.behaviors.courtRedirect = {
    attach: function (context, settings) {
      $('#search-location', context).once('courtRedirect').each(function () {
        const $form = $(this);
        $form.on('submit', function (e) {
          const courtId = $form.find('select[name="court"]').val();   
          e.preventDefault();          
          if (courtId && courtId !== '0') {
            window.location.href = '/jcc-court/' + courtId;
          } else { 
          window.location.href = 'admin/content/jcc-court'; 
        }
        });
      });
    }
  };
})(jQuery, Drupal);
