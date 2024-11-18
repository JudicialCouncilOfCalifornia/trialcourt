(function ($, Drupal) {
    console.log("me");
    console.log($(this))
    $(".form-checkbox").change(function(){
        console.log($(this).is(':checked'));
            var checked = $(this).next().text().trim()
            if(checked.toLowerCase().includes("all cases")){
                const $parentContainer = $(this).closest('#edit-field-cases-served');
                $parentContainer.find('input').prop('checked', this.checked);            
            }
        })   
})(jQuery, Drupal);