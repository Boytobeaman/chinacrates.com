<?php
class mailPps extends modulePps {
	public function init() {
		parent::init();
		//dispatcherPps::addFilter('optionsDefine', array($this, 'addOptions'));
	}
	public function send($to, $subject, $message, $fromName = '', $fromEmail = '', $replyToName = '', $replyToEmail = '', $additionalHeaders = array(), $additionalParameters = array()) {
		$headersArr = array();
		$eol = "\r\n";
        if(!empty($fromName) && !empty($fromEmail)) {
            $headersArr[] = 'From: '. $fromName. ' <'. $fromEmail. '>';
        }
		if(!empty($replyToName) && !empty($replyToEmail)) {
            $headersArr[] = 'Reply-To: '. $replyToName. ' <'. $replyToEmail. '>';
        }
		if(!function_exists('wp_mail'))
			framePps::_()->loadPlugins();
		if(!framePps::_()->getModule('options')->get('disable_email_html_type')) {
			add_filter('wp_mail_content_type', array($this, 'mailContentType'));
		}
		
		$attach = null;
		if(isset($additionalParameters['attach']) && !empty($additionalParameters['attach'])) {
			$attach = $additionalParameters['attach'];
		}
		if(empty($attach)) {
			$result = wp_mail($to, $subject, $message, implode($eol, $headersArr));
		} else {
			$result = wp_mail($to, $subject, $message, implode($eol, $headersArr), $attach);
		}
		if(!framePps::_()->getModule('options')->get('disable_email_html_type')) {
			remove_filter('wp_mail_content_type', array($this, 'mailContentType'));
		}

		return $result;
	}
	public function getMailErrors() {
		global $ts_mail_errors;
		global $phpmailer;
		// Clear prev. send errors at first
		$ts_mail_errors = array();

		// Let's try to get errors about mail sending from WP
		if (!isset($ts_mail_errors)) $ts_mail_errors = array();
		if (isset($phpmailer)) {
			$ts_mail_errors[] = $phpmailer->ErrorInfo;
		}
		if(empty($ts_mail_errors)) {
			$ts_mail_errors[] = __('Cannot send email - problem with send server');
		}
		return $ts_mail_errors;
	}
	public function mailContentType($contentType) {
		$contentType = 'text/html';
        return $contentType;
	}
	public function getTabContent() {
		return $this->getView()->getTabContent();
	}
	public function addOptions($opts) {
		$opts[ $this->getCode() ] = array(
			'label' => __('Mail', PPS_LANG_CODE),
			'opts' => array(
				'mail_function_work' => array('label' => __('Mail function tested and work', PPS_LANG_CODE), 'desc' => ''),
				'notify_email' => array('label' => __('Notify Email', PPS_LANG_CODE), 'desc' => __('Email address used for all email notifications from plugin', PPS_LANG_CODE), 'html' => 'text', 'def' => get_option('admin_email')),
			),
		);
		return $opts;
	}
}