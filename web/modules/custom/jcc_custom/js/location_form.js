(function ($, Drupal) {
  $(".form-checkbox").change(function(){
    console.log($(this).is(':checked'));
    var checked = $(this).next().text().trim()
    var $parentContainer = $(this).closest('#edit-field-cases-served-wrapper');
    if(checked.toLowerCase().includes("all cases")){
      const $parentContainer = $(this).closest('#edit-field-cases-served');
      $parentContainer.find('input').prop('checked', this.checked);
    }else{
      // If any checkbox other than "All Cases Served" is unchecked
      if (!$(this).is(':checked')) {
        // Uncheck the "All Cases Served" checkbox
        $parentContainer.find('.form-checkbox').each(function () {
          if ($(this).next().text().trim().toLowerCase().includes("all cases")) {
            $(this).prop('checked', false);
          }
        });
      }
    }
  })
})(jQuery, Drupal);
