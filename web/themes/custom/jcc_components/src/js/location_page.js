(function ($, Drupal) {
  $(".form-checkbox").change(function(){
    if($(this).is(':checked')){
      var checked = $(this).next().text().trim()
      if(checked.toLowerCase().includes("all cases")){
        const $parentContainer = $(this).closest('#edit-field-cases-served-wrapper');
        $parentContainer.find('.option').prop('checked', this.checked);
      }
    }
  })

})(jQuery, Drupal);
