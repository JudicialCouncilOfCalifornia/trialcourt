(function ($, Drupal) {
    console.log("me");
    console.log($(this))
    $(".form-checkbox").change(function(){
        console.log("something change");
        if($(this).is(':checked')){
               console.log($(this).val())
            console.log("this");
            console.log($(this).next().text().trim())
            var checked = $(this).next().text().trim()
            if(checked.toLowerCase().includes("all cases")){
                const $parentContainer = $(this).closest('#edit-field-cases-served-wrapper');
                $parentContainer.find('.option').prop('checked', this.checked);
            }else{
                const $parentContainer = $(this).closest('#edit-field-cases-served-wrapper');
                $parentContainer.find('.option').prop('checked', this.checked);
            }                   
        } 
    })

})(jQuery, Drupal);