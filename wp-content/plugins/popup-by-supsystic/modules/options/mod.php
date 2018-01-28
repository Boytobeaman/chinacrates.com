<?php
class optionsPps extends modulePps {
	private $_tabs = array();
	private $_options = array();
	private $_optionsToCategoires = array();	// For faster search
	
	public function init() {
		//dispatcherPps::addAction('afterModulesInit', array($this, 'initAllOptValues'));
		add_action('init', array($this, 'initAllOptValues'), 99);	// It should be init after all languages was inited (frame::connectLang)
		dispatcherPps::addFilter('mainAdminTabs', array($this, 'addAdminTab'));
	}
	public function initAllOptValues() {
		// Just to make sure - that we loaded all default options values
		$this->getAll();
	}
    /**
     * This method provides fast access to options model method get
     * @see optionsModel::get($d)
     */
    public function get($code) {
        return $this->getModel()->get($code);
    }
	/**
     * This method provides fast access to options model method get
     * @see optionsModel::get($d)
     */
	public function isEmpty($code) {
		return $this->getModel()->isEmpty($code);
	}
	public function getAllowedPublicOptions() {
		$allowKeys = array('add_love_link', 'disable_autosave');
		$res = array();
		foreach($allowKeys as $k) {
			$res[ $k ] = $this->get($k);
		}
		return $res;
	}
	public function getAdminPage() {
		if(installerPps::isUsed()) {
			return $this->getView()->getAdminPage();
		} else {
			installerPps::setUsed();	// Show this welcome page - only one time
			return framePps::_()->getModule('supsystic_promo')->showWelcomePage();
		}
	}
	public function addAdminTab($tabs) {
		$tabs['settings'] = array(
			'label' => __('Settings', PPS_LANG_CODE), 'callback' => array($this, 'getSettingsTabContent'), 'fa_icon' => 'fa-gear', 'sort_order' => 30,
		);
		return $tabs;
	}
	public function getSettingsTabContent() {
		return $this->getView()->getSettingsTabContent();
	}
	public function getTabs() {
		if(empty($this->_tabs)) {
			$this->_tabs = dispatcherPps::applyFilters('mainAdminTabs', array(
				//'main_page' => array('label' => __('Main Page', PPS_LANG_CODE), 'callback' => array($this, 'getTabContent'), 'wp_icon' => 'dashicons-admin-home', 'sort_order' => 0), 
			));
			foreach($this->_tabs as $tabKey => $tab) {
				if(!isset($this->_tabs[ $tabKey ]['url'])) {
					$this->_tabs[ $tabKey ]['url'] = $this->getTabUrl( $tabKey );
				}
			}
			uasort($this->_tabs, array($this, 'sortTabsClb'));
		}
		return $this->_tabs;
	}
	public function sortTabsClb($a, $b) {
		if(isset($a['sort_order']) && isset($b['sort_order'])) {
			if($a['sort_order'] > $b['sort_order'])
				return 1;
			if($a['sort_order'] < $b['sort_order'])
				return -1;
		}
		return 0;
	}
	public function getTab($tabKey) {
		$this->getTabs();
		return isset($this->_tabs[ $tabKey ]) ? $this->_tabs[ $tabKey ] : false;
	}
	public function getTabContent() {
		return $this->getView()->getTabContent();
	}
	public function getActiveTab() {
		$reqTab = reqPps::getVar('tab');
		return empty($reqTab) ? 'popup' : $reqTab;
	}
	public function getTabUrl($tab = '') {
		static $mainUrl;
		if(empty($mainUrl)) {
			$mainUrl = framePps::_()->getModule('adminmenu')->getMainLink();
		}
		return empty($tab) ? $mainUrl : $mainUrl. '&tab='. $tab;
	}
	public function getRolesList() {
		if(!function_exists('get_editable_roles')) {
			require_once( ABSPATH . '/wp-admin/includes/user.php' );
		}
		return get_editable_roles();
	}
	public function getAvailableUserRolesSelect() {
		$rolesList = $this->getRolesList();
		$rolesListForSelect = array();
		foreach($rolesList as $rKey => $rData) {
			$rolesListForSelect[ $rKey ] = $rData['name'];
		}
		return $rolesListForSelect;
	}
	public function getAll() {
		if(empty($this->_options)) {
			$this->_options = dispatcherPps::applyFilters('optionsDefine', array(
				'general' => array(
					'label' => __('General', PPS_LANG_CODE),
					'opts' => array(
						'send_stats' => array('label' => __('Send usage statistics', PPS_LANG_CODE), 'desc' => __('Send information about what plugin options you prefer to use, this will help us make our solution better for You.', PPS_LANG_CODE), 'def' => '0', 'html' => 'checkboxHiddenVal'),
						'disable_subscribe_ip_antispam' => array('label' => __('Disable blocking Subscription from same IP', PPS_LANG_CODE), 'desc' => __('By default our plugin have feature to block subscriptions from same IP more then one time per hour - to avoid spam subscribers. But you can disable this feature here.', PPS_LANG_CODE), 'def' => '1', 'html' => 'checkboxHiddenVal'),
						'disable_autosave' => array('label' => __('Disable autosave on PopUp Edit', PPS_LANG_CODE), 'desc' => __('By default our plugin will make autosave all your changes that you do in PopUp edit screen, but you can disable this feature here. Just don\'t forget to save your PopUp each time you make any changes in it.', PPS_LANG_CODE), 'def' => '0', 'html' => 'checkboxHiddenVal'),
						'add_love_link' => array('label' => __('Enable promo link', PPS_LANG_CODE), 'desc' => __('We are trying to make our plugin better for you, and you can help us with this. Just check this option - and small promotion link will be added in the bottom of your PopUp. This is easy for you - but very helpful for us!', PPS_LANG_CODE), 'def' => '0', 'html' => 'checkboxHiddenVal'),
						'access_roles' => array('label' => __('User role can use plugin', PPS_LANG_CODE), 'desc' => __('User with next roles will have access to whole plugin from admin area.', PPS_LANG_CODE), 'def' => 'administrator', 'html' => 'selectlist', 'options' => array($this, 'getAvailableUserRolesSelect'), 'pro' => ''),
						'foot_assets' => array('label' => __('Load Assets in Footer', PPS_LANG_CODE), 'desc' => __('Force load all plugin CSS and JavaScript files in footer - to increase page load speed. Please make sure that you have correct footer.php file in your WordPress theme with wp_footer() function call in it.', PPS_LANG_CODE), 'def' => '0', 'html' => 'checkboxHiddenVal'),
						'disable_email_html_type' => array('label' => __('Disable HTML Emails content type', PPS_LANG_CODE), 'desc' => __('Some servers fail send emails with HTML content type: content-type = "text/html", so if you have problems with sending emails from our plugn - try to disable this feature here.', PPS_LANG_CODE), 'def' => '0', 'html' => 'checkboxHiddenVal'),
						'use_local_cdn' => array('label' => __('Disable CDN usage', PPS_LANG_CODE), 'desc' => esc_html(sprintf(__('By default our plugin is using CDN server to store there part of it\'s files - images, javascript and CSS libraries. This was designed in that way to reduce plugin size, make it lighter and easier for usage. But if you need to store all files - on your server - you can disable this option here, then upload plugin CDN files to your own site. To make it work correctly - check our article that describe how you need to do this <a href="%s" target="_blank">here</a>.', PPS_LANG_CODE), 'http://supsystic.com/disable-cdn-usage-popup-plugin/')), 'def' => '0', 'html' => 'checkboxHiddenVal'),
					),
				),
			));
			$isPro = framePps::_()->getModule('supsystic_promo')->isPro();
			foreach($this->_options as $catKey => $cData) {
				foreach($cData['opts'] as $optKey => $opt) {
					$this->_optionsToCategoires[ $optKey ] = $catKey;
					if(isset($opt['pro']) && !$isPro) {
						$this->_options[ $catKey ]['opts'][ $optKey ]['pro'] = framePps::_()->getModule('supsystic_promo')->generateMainLink('utm_source=plugin&utm_medium='. $optKey. '&utm_campaign=popup');
					}
				}
			}
			$this->getModel()->fillInValues( $this->_options );
		}
		return $this->_options;
	}
	public function getFullCat($cat) {
		$this->getAll();
		return isset($this->_options[ $cat ]) ? $this->_options[ $cat ] : false;
	}
	public function getCatOpts($cat) {
		$opts = $this->getFullCat($cat);
		return $opts ? $opts['opts'] : false;
	}
}
