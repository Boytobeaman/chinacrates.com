jQuery(document).ready(function(){
	jQuery('#ppsSettingsSaveBtn').click(function(){
		jQuery('#ppsSettingsForm').submit();
		return false;
	});
	jQuery('#ppsSettingsForm').submit(function(){
		jQuery(this).sendFormPps({
			btn: jQuery('#ppsSettingsSaveBtn')
		});
		return false;
	});
});