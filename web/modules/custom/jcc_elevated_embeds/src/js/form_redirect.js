(function ($, Drupal) {

  Drupal.behaviors.emptyKeywordRedirect = {
    attach: function (context) {
      $(once('emptyKeywordRedirect', '#court-search-officer', context))
        .on('submit', function (e) {
          const keyword = $(this).find('input[name="keyword"]').val().trim();
          if (keyword === '') {
            e.preventDefault();
            window.location.href = '/directory/jcc-officer';
            return;
          }
          const target = '/directory/jcc-officer?keyword=' + keyword;
          e.preventDefault();                 
          window.location.href = target;      
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
          window.location.href = '/directory/jcc-court'; 
        }
        });
      });
    }
  };
})(jQuery, Drupal);
