jQuery('main').find("a[href*='#']").click(function(e){
	var elemId = jQuery(this).attr('href');
	elemId = elemId.replace('#','');
	if (jQuery('#' + elemId).parents('.usa-accordion__content').attr('hidden')) {
		jQuery('#' + elemId).parents('.usa-accordion__content').removeAttr('hidden');
	}
});
