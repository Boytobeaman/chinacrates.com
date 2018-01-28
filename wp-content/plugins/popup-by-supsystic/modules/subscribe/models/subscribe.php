<?php
class subscribeModelPps extends modelPps {
	private $_dest = '';
	private $_lastPopup = null;	// Some small internal caching
	private $_subscribedInternal = true;
	
	private $_emailExists = false;
	
	private $_alreadySubscribedSuccess = false;
	public function __construct() {
		$this->_setTbl('subscribers');
	}
	public function subscribe($d = array(), $validateIp = false) {
		$id = isset($d['id']) ? $d['id'] : 0;
		if($id) {
			$popup = framePps::_()->getModule('popup')->getModel()->getById($id);
			if($popup && isset($popup['params']) 
				&& isset($popup['params']['tpl']['enb_subscribe']) 
				&& isset($popup['params']['tpl']['sub_dest'])
			) {
				$dest = $popup['params']['tpl']['sub_dest'];
				$subMethod = 'subscribe_'. $dest;
				$this->_subscribedInternal = method_exists($this, $subMethod);	// Subscribe method it in this model
				$externalModel = NULL;
				if(!$this->_subscribedInternal) {
					// Check subscribe method in other modules - from PRO version for example
					$externalModel = framePps::_()->getModule($dest) ? framePps::_()->getModule($dest)->getModel() : NULL;
				}
				if($this->_subscribedInternal || $externalModel) {
					$this->_dest = $dest;
					$this->_lastPopup = $popup;
					$d = dbPps::prepareHtmlIn($d);
					if($this->validateFields($d, $popup)) {
						$res = $this->_subscribedInternal 
							? $this->$subMethod($d, $popup, $validateIp) 
							: $externalModel->subscribe($d, $popup, $validateIp);
						if(!$res && !$this->_subscribedInternal) {
							$externalErrors = $externalModel->getErrors();
							if(!empty($externalErrors)) {
								$this->pushError($externalErrors);
							}
						}
						if($res) {
							$this->checkSendNewNotification($d, $popup);
							$this->checkCreateWpUser($d, $popup, $dest);
						}
						return $res;
					}
				} else
					$this->pushError (__('Something goes wrong', PPS_LANG_CODE));
			} else
				$this->pushError (__('Empty or invalid ID', PPS_LANG_CODE));
		} else
			$this->pushError (__('Empty or invalid ID', PPS_LANG_CODE));
		return false;
	}
	public function checkCreateWpUser($d, $popup, $dest) {
		if(isset($popup['params']['tpl']['sub_create_wp_user']) 
			&& $popup['params']['tpl']['sub_create_wp_user']
			&& $dest != 'wordpress'
		) {
			$this->subscribe_wordpress($d, $popup, false, false, true);
		}
	}
	public function checkSendNewNotification($d, $popup, $forReg = false) {
		$pref = $forReg ? 'reg' : 'sub';
		$adminEmail = get_bloginfo('admin_email');
		if(!isset($popup['params']['tpl'][$pref. '_new_email'])) {
			$sendTo = $adminEmail;	// For case when it was just not existed before, but notification should still come
		} else {
			$sendTo = isset($popup['params']['tpl'][$pref. '_new_email']) && !empty($popup['params']['tpl'][$pref. '_new_email'])
				? trim($popup['params']['tpl'][$pref. '_new_email'])
				: false;
		}
		if(!empty($sendTo)) {
			$blogName = wp_specialchars_decode(get_bloginfo('name'));
			$defSubject = $forReg ? __('New User notification', PPS_LANG_CODE) : __('New Subscriber notification', PPS_LANG_CODE);
			$emailSubject = empty($blogName) 
				? $defSubject
				: sprintf(__('New Subscriber on %s', PPS_LANG_CODE), $blogName);
			if(isset($popup['params']['tpl'][$pref. '_new_subject']) 
				&& !empty($popup['params']['tpl'][$pref. '_new_subject'])
			) {
				$emailSubject = $popup['params']['tpl'][$pref. '_new_subject'];
			}
			$defContent = $forReg 
				? __('You have new user registration on your site <a href="[siteurl]">[sitename]</a>, here us user information:<br />[subscriber_data]', PPS_LANG_CODE)
				: __('You have new subscriber on your site <a href="[siteurl]">[sitename]</a>, here is subscriber information:<br />[subscriber_data]', PPS_LANG_CODE);
			$emailContent = isset($popup['params']['tpl'][$pref. '_new_message']) && !empty($popup['params']['tpl'][$pref. '_new_message'])
				? $popup['params']['tpl'][$pref. '_new_message']
				: $defContent;
			$subscriberDataArr = array();
			if(isset($popup['params']['tpl'][$pref. '_fields'])
				&& !empty($popup['params']['tpl'][$pref. '_fields'])
			) {
				foreach($popup['params']['tpl'][$pref. '_fields'] as $k => $f) {
					if(isset($d[ $k ])) {
						$subscriberDataArr[] = sprintf($f['label']. ': %s', $d[ $k ]) . '<br />';
					}
				}
			}
			$replaceVariables = array(
				'sitename' => $blogName,
				'siteurl' => get_bloginfo('wpurl'),
				'subscriber_data' => implode('<br />', $subscriberDataArr),
				'subscribe_url' => dbPps::prepareHtmlIn(reqPps::getVar('HTTP_REFERER', 'server')),
			);
			foreach($replaceVariables as $k => $v) {
				$emailSubject = str_replace('['. $k. ']', $v, $emailSubject);
				$emailContent = str_replace('['. $k. ']', $v, $emailContent);
			}
			$addMailParams = array();
			// Check attachments
			$attach = $this->_extractAttach($popup, $pref. '_attach_new_message');
			if(!empty($attach)) {
				$addMailParams['attach'] = $attach;
			}
			framePps::_()->getModule('mail')->send($sendTo,
				$emailSubject,
				$emailContent,
				$blogName,
				$adminEmail,
				$blogName,
				$adminEmail,
				array(),
				$addMailParams);
		}
	}
	public function isSubscribedInternal() {
		return $this->_subscribedInternal;
	}
	public function validateFields($d, $popup, $forReg = false) {
		$pref = $forReg ? 'reg' : 'sub';
		if(isset($popup['params']['tpl'][$pref. '_fields']) && !empty($popup['params']['tpl'][$pref. '_fields'])) {
			$errors = array();
			foreach($popup['params']['tpl'][$pref. '_fields'] as $k => $f) {
				if(isset($f['enb']) && $f['enb'] && isset($f['mandatory']) && $f['mandatory']) {
					$value = isset($d[ $k ]) ? trim($d[ $k ]) : false;
					if(empty($value)) {
						$msg = '';
						switch($f['html']) {
							case 'selectbox':
								$errors[ $k ] = __('Please select %s', PPS_LANG_CODE);
								break;
							case 'checkbox':
								$errors[ $k ] = __('Please check %s', PPS_LANG_CODE);
								break;
							default:
								$errors[ $k ] = __('Please enter %s', PPS_LANG_CODE);
								break;
						}
						$errors[ $k ] = sprintf($errors[ $k ], $f['label']);
					}
				}
			}
			if(!empty($errors)) {
				$this->pushError($errors);
				return false;
			}
		}
		return true;
	}
	public function getDest() {
		return $this->_dest;
	}
	public function getLastPopup() {
		return $this->_lastPopup;
	}
	/**
	 * Just public alias for private method
	 */
	public function checkOftenAccess($d = array()) {
		return $this->_checkOftenAccess( $d );
	}
	private function _checkOftenAccess($d = array()) {
		if((int) framePps::_()->getModule('options')->get('disable_subscribe_ip_antispam'))
			return true;
		//return true;
		$onlyCheck = isset($d['only_check']) ? $d['only_check'] : false;
		$onlyAdd = isset($d['only_add']) ? $d['only_add'] : false;
		$ip = utilsPps::getIP();
		if(empty($ip)) {
			$this->pushError(__('Can\'t detect your IP, please don\'t spam', PPS_LANG_CODE));
			return false;
		}
		$accessByIp = get_option(PPS_CODE. '_access_py_ip');
		if(empty($accessByIp)) {
			$accessByIp = array();
		}
		$time = time();
		$break = false;
		if($onlyAdd) {
			$accessByIp[ $ip ] = $time;
			update_option(PPS_CODE. '_access_py_ip', $accessByIp);
			return true;
		}
		// Clear old values
		if(!empty($accessByIp)) {
			foreach($accessByIp as $k => $v) {
				if($time - (int) $v >= 3600)
					unset($accessByIp[ $k ]);
			}
		}
		if(isset($accessByIp[ $ip ])) {
			if($time - (int) $accessByIp[ $ip ] <= 30 * 60) {
				$break = true;
			} else
				$accessByIp[ $ip ] = $time;
		} else {
			$accessByIp[ $ip ] = $time;
		}
		if(!$onlyCheck)
			update_option(PPS_CODE. '_access_py_ip', $accessByIp);
		if($break) {
			$this->pushError(__('You just subscribed from this IP', PPS_LANG_CODE));
			return false;
		}
		return true;
	}
	/**
	 * Public alias for _getInvalidEmailMsg() method
	 */
	public function getInvalidEmailMsg($popup = array()) {
		return $this->_getInvalidEmailMsg($popup);
	}
	private function _getInvalidEmailMsg($popup = array(), $forReg = false, $existsProblem = false) {
		$pref = $forReg ? 'reg' : 'sub';
		$msg = !empty($popup) && isset($popup['params']['tpl'][$pref. '_txt_invalid_email'])
			? $popup['params']['tpl'][$pref. '_txt_invalid_email']
			: __('Empty or invalid email', PPS_LANG_CODE);
		if($existsProblem 
			&& !empty($popup) 
			&& isset($popup['params']['tpl'][$pref. '_txt_exists_email']) 
			&& !empty($popup['params']['tpl'][$pref. '_txt_exists_email'])
		) {
			$msg = $popup['params']['tpl'][$pref. '_txt_exists_email'];
		}
		return $msg;
	}
	/**
	 * WordPress subscribe functionality
	 */
	public function subscribe_wordpress($d, $popup, $validateIp = false, $forReg = false, $forceIgnoreConfirm = false) {
		$email = isset($d['email']) ? trim($d['email']) : false;
		if(!empty($email)) {
			if(is_email($email)) {
				if(!email_exists($email)) {
					if(!$validateIp || $validateIp && $this->_checkOftenAccess()) {
						$username = '';
						$pref = $forReg ? 'reg' : 'sub';
						if((isset($popup['params']['tpl']['enb_sub_name']) && $popup['params']['tpl']['enb_sub_name']) || isset($d['name'])) {
							$username = trim($d['name']);
						}
						$username = $this->_getUsernameFromEmail($email, $username);
						$ignoreConfirm = (isset($popup['params']['tpl'][$pref. '_ignore_confirm']) && $popup['params']['tpl'][$pref. '_ignore_confirm']) || $forceIgnoreConfirm;
						$confirmHash = md5($email. NONCE_KEY);
						$saveData = array(
							'username' => $username,
							'email' => $email,
							'hash' => $confirmHash,
							'popup_id' => $popup['id'],
							'all_data' => utilsPps::serialize( $d ),
							'activated' => $ignoreConfirm ? 1 : 0,
						);
						if($forReg) {
							$popupSubId = framePps::_()->getModule('login')->getModel()->insert( $saveData );
							if(!$popupSubId) {
								$this->pushError( framePps::_()->getModule('login')->getModel()->getErrors() );
							}
						} else {
							$popupSubId = $this->insert( $saveData );
						}
						if($ignoreConfirm) {
							return $this->createWpSubscriber($popup, $email, $username, $d, $forReg);
						} else {
							if($popupSubId) {
								$this->sendWpUserConfirm($username, $email, $confirmHash, $popup, $forReg, $d);
								return true;
							}
						}
					}
				} else {
					$this->setEmailExists(true);
					$this->pushError ($this->_getInvalidEmailMsg($popup, $forReg, true), 'email');
				}
			} else
				$this->pushError ($this->_getInvalidEmailMsg($popup, $forReg), 'email');
		} else
			$this->pushError ($this->_getInvalidEmailMsg($popup, $forReg), 'email');
		return false;
	}
	public function setEmailExists($state) {
		$this->_emailExists = $state;
	}
	public function getEmailExists($email = '') {
		if(!empty($email)) {
			$this->setEmailExists( email_exists($email) );
		}
		return $this->_emailExists;
	}
	public function createWpSubscriber($popup, $email, $username, $d, $forReg = false) {
		$pref = $forReg ? 'reg' : 'sub';
		$password = wp_generate_password();
		$userId = wp_create_user($username, $password, $email);
		if($userId && !is_wp_error($userId)) {
			if(!function_exists('wp_new_user_notification')) {
				framePps::_()->loadPlugins();
			}
			// If there was selected some special role - check it here
			$this->_lastPopup = $popup;
			if(isset($popup['params']['tpl'][$pref. '_wp_create_user_role']) 
				&& !empty($popup['params']['tpl'][$pref. '_wp_create_user_role']) 
				&& $popup['params']['tpl'][$pref. '_wp_create_user_role'] != 'subscriber'
			) {
				$user = new WP_User($userId);
				$user->set_role( $popup['params']['tpl'][$pref. '_wp_create_user_role'] );
			}
			if(isset($popup['params']['tpl'][$pref. '_fields'])
				&& !empty($popup['params']['tpl'][$pref. '_fields'])
			) {
				foreach($popup['params']['tpl'][$pref. '_fields'] as $k => $f) {
					if(in_array($k, array('name', 'email'))) continue;	// Ignore standard fields
					if(isset($d[ $k ])) {
						wp_update_user(array('ID' => $userId, $k => $d[ $k ]));
					}
				}
			}
			$this->_sendNewUserNotification($popup, $userId, $password, $d, $forReg);
			return true;
		} else {
			$defaultErrorMsg = $forReg ? __('Can\'t registrate for now. Please try again later.', PPS_LANG_CODE) : __('Can\'t subscribe for now. Please try again later.', PPS_LANG_CODE);
			$this->pushError (is_wp_error($userId) ? $userId->get_error_message() : $defaultErrorMsg);
		}
		return false;
	}
	private function _sendNewUserNotification($popup, $userId, $password, $d, $forReg = false) {
		$pref = $forReg ? 'reg' : 'sub';
		$emailSubject = isset($popup['params']['tpl'][$pref. '_txt_subscriber_mail_subject']) ? $popup['params']['tpl'][$pref. '_txt_subscriber_mail_subject'] : false;
		$emailContent = isset($popup['params']['tpl'][$pref. '_txt_subscriber_mail_message']) ? $popup['params']['tpl'][$pref. '_txt_subscriber_mail_message'] : false;
		if($emailSubject && $emailContent) {
			$user = get_userdata( $userId );
			$blogName = wp_specialchars_decode(get_bloginfo('name'));
			$adminEmail = isset($popup['params']['tpl'][$pref. '_txt_subscriber_mail_from']) 
				? $popup['params']['tpl'][$pref. '_txt_subscriber_mail_from']
				: get_bloginfo('admin_email');
			$replaceVariables = array(
				'sitename' => $blogName,
				'siteurl' => get_bloginfo('wpurl'),
				'user_login' => $user->user_login,
				'user_email' => $user->user_email,
				'password' => $password,
				'login_url' => wp_login_url(),
				'subscribe_url' => dbPps::prepareHtmlIn(reqPps::getVar('HTTP_REFERER', 'server')),
			);
			if(!empty($d)) {
				foreach($d as $k => $v) {
					$replaceVariables[ 'user_'. $k ] = $v;
				}
			}
			foreach($replaceVariables as $k => $v) {
				$emailSubject = str_replace('['. $k. ']', $v, $emailSubject);
				$emailContent = str_replace('['. $k. ']', $v, $emailContent);
			}
			$addMailParams = array();
			// Check attachments
			$attach = $this->_extractAttach($popup, 'sub_attach_subscriber');
			if(!empty($attach)) {
				$addMailParams['attach'] = $attach;
			}
			framePps::_()->getModule('mail')->send($user->user_email,
				$emailSubject,
				$emailContent,
				$blogName,
				$adminEmail,
				$blogName,
				$adminEmail,
				array(),
				$addMailParams);
		} else {	// Just use standard wp method
			if(isset($popup['params']['tpl'][$pref. '_txt_subscriber_mail_subject']) 
				&& empty($popup['params']['tpl'][$pref. '_txt_subscriber_mail_subject'])
				&& isset($popup['params']['tpl'][$pref. '_txt_subscriber_mail_message']) 
				&& empty($popup['params']['tpl'][$pref. '_txt_subscriber_mail_message'])
			) {
				// User do not want any notifications - just don't send them at all
				return;
			}
			wp_new_user_notification($userId, $password);
		}
	}
	public function sendWpUserConfirm($username, $email, $confirmHash, $popup, $forReg = false, $d = array()) {
		$pref = $forReg ? 'reg' : 'sub';
		$blogName = wp_specialchars_decode(get_bloginfo('name'));
		$confirmLinkData = array('email' => $email, 'hash' => $confirmHash);
		if($forReg) {
			$confirmLinkData['for_reg'] = 1;
		}
		$replaceVariables = array(
			'sitename' => $blogName,
			'siteurl' => get_bloginfo('wpurl'),
			'confirm_link' => uriPps::mod('subscribe', 'confirm', $confirmLinkData),
			'subscribe_url' => dbPps::prepareHtmlIn(reqPps::getVar('HTTP_REFERER', 'server')),
		);
		if(!empty($d)) {
			foreach($d as $k => $v) {
				$replaceVariables[ 'user_'. $k ] = $v;
			}
		}
		$adminEmail = isset($popup['params']['tpl'][$pref. '_txt_confirm_mail_from']) 
			? $popup['params']['tpl'][$pref. '_txt_confirm_mail_from']
			: get_bloginfo('admin_email');
		$confirmSubject = isset($popup['params']['tpl'][$pref. '_txt_confirm_mail_subject']) && !empty($popup['params']['tpl'][$pref. '_txt_confirm_mail_subject'])
				? $popup['params']['tpl'][$pref. '_txt_confirm_mail_subject']
				: __('Confirm subscription on [sitename]', PPS_LANG_CODE);
		$confirmContent = isset($popup['params']['tpl'][$pref. '_txt_confirm_mail_message']) && !empty($popup['params']['tpl'][$pref. '_txt_confirm_mail_message'])
				? $popup['params']['tpl'][$pref. '_txt_confirm_mail_message']
				: __('You subscribed on site <a href="[siteurl]">[sitename]</a>. Follow <a href="[confirm_link]">this link</a> to complete your subscription. If you did not subscribe here - just ignore this message.', PPS_LANG_CODE);
		foreach($replaceVariables as $k => $v) {
			$confirmSubject = str_replace('['. $k. ']', $v, $confirmSubject);
			$confirmContent = str_replace('['. $k. ']', $v, $confirmContent);
		}
		$addMailParams = array();
		// Check attachments
		$attach = $this->_extractAttach($popup, 'sub_attach_confirm');
		if(!empty($attach)) {
			$addMailParams['attach'] = $attach;
		}
		framePps::_()->getModule('mail')->send($email,
			$confirmSubject,
			$confirmContent,
			$blogName,
			$adminEmail,
			$blogName,
			$adminEmail,
			array(),
			$addMailParams);
	}
	private function _extractAttach(&$popup, $key) {
		$res = array();
		if(isset($popup['params']['tpl'][ $key ]) && !empty($popup['params']['tpl'][ $key ])) {
			foreach($popup['params']['tpl'][ $key ] as $attach) {
				if(empty($attach)) continue;
				$attachPath = str_replace(content_url(), WP_CONTENT_DIR, $attach);
				$res[] = $attachPath;
			}
		}
		return $res;
	}
	public function confirm($d = array(), $forReg = false) {
		$d['email'] = isset($d['email']) ? trim($d['email']) : '';
		$d['hash'] = isset($d['hash']) ? trim($d['hash']) : '';
		$popup = array();
		if(!empty($d['email']) && !empty($d['hash'])) {
			$selectSubscriberData = array(
				'email' => $d['email'],
				'hash' => $d['hash'], 
				'activated' => 0,
			);
			$subscribeModel = $forReg ? framePps::_()->getModule('login')->getModel() : $this;
			$subscriber = $subscribeModel
				->setWhere($selectSubscriberData)
				->getFromTbl(array('return' => 'row'));
			if(empty($subscriber) && $forReg) {
				$this->pushError( framePps::_()->getModule('login')->getModel()->getErrors() );
			}
			if(!empty($subscriber)) {
				if(isset($subscriber['popup_id']) && !empty($subscriber['popup_id'])) {
					$popup = framePps::_()->getModule('popup')->getModel()->getById($subscriber['popup_id']);
					$this->_lastPopup = $popup;
				}
				$subscriber['all_data'] = isset($subscriber['all_data']) ? utilsPps::unserialize($subscriber['all_data']) : array();
				$res = $this->createWpSubscriber($popup, $subscriber['email'], $subscriber['username'], $subscriber['all_data'], $forReg);
				if($res) {
					$subscribeModel->update(array('activated' => 1), array('id' => $subscriber['id']));
				}
				return $res;
			}
		}
		// One and same error for all other cases
		$this->pushError(__('Send me some info, pls', PPS_LANG_CODE));
		return false;
	}
	/*
	 * Public alias for _getUsernameFromEmail() method
	 */
	public function getUsernameFromEmail($email, $username = '') {
		return $this->_getUsernameFromEmail($email, $username);
	}
	private function _getUsernameFromEmail($email, $username = '') {
		if(!empty($username)) {
			if(username_exists($username)) {
				return $this->_getUsernameFromEmail($email, $username. mt_rand(1, 9999));
			}
			return $username;
		} else {
			$nameHost = explode('@', $email);
			if(username_exists($nameHost[0])) {
				return $this->_getUsernameFromEmail($nameHost[0]. mt_rand(1, 9999). '@'. $nameHost[1], $username);
			}
			return $nameHost[0];
		}
	}
	/**
	 * MailChimp functions
	 */
	private function _getMailchimpInst($key) {
		static $instances = array();
		if(!isset($instances[ $key ])) {
			if(!class_exists('mailChimpClientPps'))
				require_once($this->getModule()->getModDir(). 'classes'. DS. 'mailChimpClient.php');
			$instances[ $key ] = new mailChimpClientPps( $key );
		}
		return $instances[ $key ];
	}
	public function isMailchimpSupported() {
		if(!function_exists('curl_init')) {
			$this->pushError(__('MailChimp requires CURL to be setup on your server. Please contact your hosting provider and ask them to setup CURL library for you.', PPS_LANG_CODE));
			return false;
		}
		return true;
	}
	public function getMailchimpLists($d = array()) {
		if(!$this->isMailchimpSupported())
			return false;
		$key = isset($d['key']) ? trim($d['key']) : '';
		if(!empty($key)) {
			$client = $this->_getMailchimpInst( $key );
			$apiRes = $client->call('lists/list', array('limit' => 100, 'sort_field' => 'web'));
			if($apiRes && is_array($apiRes) && isset($apiRes['data']) && !empty($apiRes['data'])) {
				$listsDta = array();
				foreach($apiRes['data'] as $list) {
					$listsDta[ $list['id'] ] = $list['name'];
				}
				return $listsDta;
			} else {
				if(isset($apiRes['errors']) && !empty($apiRes['errors'])) {
					$this->pushError($apiRes['errors']);
				} elseif(isset($apiRes['error']) && !empty($apiRes['error'])) {
					$this->pushError($apiRes['error']);
				} else {
					$this->pushError(__('There was some problem while trying to get your lists. Make sure that your API key is correct.', PPS_LANG_CODE));
				}
			}
		} else
			$this->pushError(__('Empty API key', PPS_LANG_CODE));
		return false;
	}
	public function getMailchimpGroups($d = array()) {
		if(!$this->isMailchimpSupported())
			return false;
		$key = isset($d['key']) ? trim($d['key']) : '';
		$listIds = isset($d['listIds']) && is_array($d['listIds']) && !empty($d['listIds']) 
			? array_map('trim', $d['listIds']) 
			: false;
		if(!empty($key)) {
			if(!empty($listIds)) {
				$client = $this->_getMailchimpInst( $key );
				$groups = array();
				foreach($listIds as $lid) {
					$apiRes = $client->call('lists/interest-groupings', array('id' => $lid));
					if($apiRes && is_array($apiRes) && !isset($apiRes['errors']) && !isset($apiRes['error'])) {
						foreach($apiRes as $pGroup) {
							if(isset($pGroup['groups']) && !empty($pGroup['groups'])) {
								foreach($pGroup['groups'] as $group) {
									$groups[ $lid. '|'. $pGroup['id']. '|'. base64_encode($group['name']) ] = $pGroup['name']. ' - '. $group['name'];
								}
							}
						}
					} else {
						if(isset($apiRes['errors']) && !empty($apiRes['errors'])) {
							$this->pushError($apiRes['errors']);
						} elseif(isset($apiRes['error']) && !empty($apiRes['error'])) {
							$this->pushError($apiRes['error']);
						} else {
							$this->pushError(__('There was some problem while trying to get your lists. Make sure that your API key is correct.', PPS_LANG_CODE));
						}
					}
				}
				return $groups;
			} else
				$this->pushError(__('Select some Lists before', PPS_LANG_CODE));
		} else
			$this->pushError(__('Empty API key', PPS_LANG_CODE));
		return false;
	}
	public function subscribe_mailchimp($d, $popup, $validateIp = false) {
		$email = isset($d['email']) ? trim($d['email']) : false;
		if(!empty($email)) {
			if(is_email($email)) {
				if(!$this->isMailchimpSupported())
					return false;
				$lists = isset($popup['params']['tpl']['sub_mailchimp_lists']) ? $popup['params']['tpl']['sub_mailchimp_lists'] : array();
				$apiKey = isset($popup['params']['tpl']['sub_mailchimp_api_key']) ? trim($popup['params']['tpl']['sub_mailchimp_api_key']) : array();
				if(!empty($lists)) {
					if(!empty($apiKey)) {
						if(!$validateIp || $validateIp && $this->_checkOftenAccess(array('only_check' => true))) {
							$name = '';
							if(isset($popup['params']['tpl']['enb_sub_name']) && $popup['params']['tpl']['enb_sub_name']) {
								$name = trim($d['name']);
							}
							$client = $this->_getMailchimpInst( $apiKey );
							$member = array(
								'email' => $email,
							);
							$dataToSend = array('email' => $member);
							if(!empty($name)) {
								$firstLastNames = array_map('trim', explode(' ', $name));
								$dataToSend['merge_vars'] = array(
									'FNAME' => $firstLastNames[ 0 ],
								);
								if(isset($firstLastNames[ 1 ]) && !empty($firstLastNames[ 1 ])) {
									$dataToSend['merge_vars']['LNAME'] = $firstLastNames[ 1 ];
								}
								$dataToSend['merge_vars']['NAME'] = $name;
							}
							if(isset($popup['params']['tpl']['sub_fields'])
								&& !empty($popup['params']['tpl']['sub_fields'])
							) {
								foreach($popup['params']['tpl']['sub_fields'] as $k => $f) {
									if(in_array($k, array('name', 'email'))) continue;	// Ignore standard fields
									if(isset($d[ $k ])) {
										if(!isset($dataToSend['merge_vars']))
											$dataToSend['merge_vars'] = array();
										$dataToSend['merge_vars'][$k] = $d[ $k ];
									}
								}
							}
							// Disable double opt-in
							if(isset($popup['params']['tpl']['sub_dsbl_dbl_opt_id']) && $popup['params']['tpl']['sub_dsbl_dbl_opt_id']) {
								$dataToSend['double_optin'] = false;
								if(isset($popup['params']['tpl']['sub_mc_enb_welcome']) && $popup['params']['tpl']['sub_mc_enb_welcome']) {
									$dataToSend['send_welcome'] = true;
								}
							}
							foreach($lists as $listId) {
								$dataToSend['id'] = $listId;
								if(isset($dataToSend['merge_vars']['groupings']))	// It can be set from prev. subscribe list
									unset($dataToSend['merge_vars']['groupings']);
								// Check if groups was selected for this list
								if(isset($popup['params']['tpl']['sub_mailchimp_groups']) && !empty($popup['params']['tpl']['sub_mailchimp_groups'])) {
									$grouping = array();
									// It can be set from PopUp using special subscribe additional field with type - mailchimp_groups_list
									if(isset($d['mailchimp_goup'])) {
										$listParentGroupGroupIds = explode('|', $d['mailchimp_goup']);
										if(count($listParentGroupGroupIds) == 3) {
											if(!isset($grouping[ $listParentGroupGroupIds[ 1 ] ]))
												$grouping[ $listParentGroupGroupIds[ 1 ] ] = array('id' => $listParentGroupGroupIds[ 1 ], 'groups' => array());
											$grouping[ $listParentGroupGroupIds[1] ]['groups'][] = base64_decode( $listParentGroupGroupIds[ 2 ] );
										}
									} else {
										foreach($popup['params']['tpl']['sub_mailchimp_groups'] as $group) {
											$listParentGroupGroupIds = explode('|', $group);
											if($listParentGroupGroupIds[ 0 ] == $listId) {
												if(!isset($grouping[ $listParentGroupGroupIds[ 1 ] ]))
													$grouping[ $listParentGroupGroupIds[ 1 ] ] = array('id' => $listParentGroupGroupIds[ 1 ], 'groups' => array());
												$grouping[ $listParentGroupGroupIds[ 1 ] ]['groups'][] = base64_decode( $listParentGroupGroupIds[ 2 ] );
											}
										}
									}
									if(!empty($grouping)) {
										foreach($grouping as $g) {
											$dataToSend['merge_vars']['groupings'][] = $g;
										}
									}
								}
								$res = $client->call('lists/subscribe', $dataToSend);
								if(!$res) {
									$this->pushError (__('Something going wrong while trying to send data to MailChimp. Please contact site owner.', PPS_LANG_CODE));
									return false;
								} elseif(isset($res['status']) && $res['status'] == 'error') {
									if(isset($res['name']) && $res['name'] == 'List_AlreadySubscribed') {
										$this->_alreadySubscribedSuccess = true;
										continue;	// User already subscribed - then ok, just go futher
									} elseif(isset($res['name']) && $res['name'] == 'List_MergeFieldRequired') {
										$res['error'] = $this->_mailchimpReplaceTagVariables($client, $lists, $listId, $res['error']);
									}
									$this->pushError ( $res['error'] );
									return false;
								}
							}
							if($validateIp) {
								$this->_checkOftenAccess(array('only_add' => true));
							}
							return true;
						}
					} else
						$this->pushError (__('No API key entered in admin area - contact site owner to resolve this issue.', PPS_LANG_CODE));
				} else
					$this->pushError (__('No lists to add selected in admin area - contact site owner to resolve this issue.', PPS_LANG_CODE));
			} else
				$this->pushError ($this->_getInvalidEmailMsg($popup), 'email');
		} else
			$this->pushError ($this->_getInvalidEmailMsg($popup), 'email');
		return false;
	}
	/**
	 * Replace MailChimp list bariables tags - into their names - to make it understandable for users
	 * @param object $client Mailchimp API client
	 * @param array $lists All lists IDs - to retrive variables for all lists in one time
	 * @param numeric $listId Current List ID - to find variable name in exactly this list
	 * @param string $str String for replace
	 * @return string Modified (replaced) $str parameter
	 */
	private function _mailchimpReplaceTagVariables($client, $lists, $listId, $str) {
		// We can't use here only one variable - as result for get list variables can fail 
		// - and we don't need to re-send it again
		static $listsVariables = false;
		static $listsVariablesCalled = false;
		if(!$listsVariablesCalled) {
			$listsVariablesRes = $client->call('lists/merge-vars', array('id' => $lists));
			if($listsVariablesRes && isset($listsVariablesRes['data']) && !empty($listsVariablesRes['data'])) {
				$listsVariables = array();
				foreach($listsVariablesRes['data'] as $lvrd) {
					$listsVariables[ $lvrd['id'] ] = array();
					if(isset($lvrd['merge_vars']) && !empty($lvrd['merge_vars'])) {
						foreach($lvrd['merge_vars'] as $mergeVar) {
							$listsVariables[ $lvrd['id'] ][ $mergeVar['tag'] ] = $mergeVar['name'];
						}
					}
				} 
			}
			$listsVariablesCalled = true;
		}
		if($listsVariables && isset($listsVariables[ $listId ]) && !empty($listsVariables[ $listId ])) {
			foreach($listsVariables[ $listId ] as $mvTag => $mvName) {
				$str = str_replace($mvTag, $mvName, $str);
			}
		}
		// Remove standard notification about entering value: obviously if there are error here - you need to enter value
		$str = trim(str_replace('- Please enter a value', '', $str));
		return $str;
	}
	public function alreadySubscribedSuccess() {
		return $this->_alreadySubscribedSuccess;
	}
	public function subscribe_mailpoet($d, $popup, $validateIp = false) {
		$email = isset($d['email']) ? trim($d['email']) : false;
		if(!empty($email) && is_email($email)) {
			if(!$validateIp || $validateIp && $this->_checkOftenAccess(array('only_check' => true))) {
				if(class_exists('WYSIJA')) {
					$name = '';
					if(isset($popup['params']['tpl']['enb_sub_name']) && $popup['params']['tpl']['enb_sub_name']) {
						$name = trim($d['name']);
					}
					$userData = array('email' => $email);
					if(!empty($name)) {
						$firstLastNames = array_map('trim', explode(' ', $name));
						$userData['firstname'] = $firstLastNames[ 0 ];
						if(isset($firstLastNames[ 1 ]) && !empty($firstLastNames[ 1 ])) {
							$userData['lastname'] = $firstLastNames[ 1 ];
						}
					}
					$userFields = array();
					if(isset($popup['params']['tpl']['sub_fields'])
						&& !empty($popup['params']['tpl']['sub_fields'])
					) {
						foreach($popup['params']['tpl']['sub_fields'] as $k => $f) {
							if(in_array($k, array('name', 'email'))) continue;	// Ignore standard fields
							if(isset($d[ $k ])) {
								$userFields[$k] = $d[ $k ];
							}
						}
					}
					$dataSubscriber = array(
						'user' => $userData,
						'user_list' => array('list_ids' => array( $popup['params']['tpl']['sub_mailpoet_list'] )),
					);
					if(!empty($userFields)) {
						$dataSubscriber['user_field'] = $userFields;
					}
					$helperUser = WYSIJA::get('user', 'helper');
					if($helperUser->addSubscriber($dataSubscriber)) {
						if($validateIp) {
							$this->_checkOftenAccess(array('only_add' => true));
						}
						return true;
					} else {
						$messages = $helperUser->getMsgs();
						$this->pushError( (!empty($messages) && isset($messages['error']) && !empty($messages['error']) ? $messages['error'] : __('Some error occured during subscription process', PPS_LANG_CODE))); 
					}
				} else
					$this->pushError (__('Can\'t find MailPoet on this server', PPS_LANG_CODE));
			}
		} else
			$this->pushError ($this->_getInvalidEmailMsg($popup), 'email');
		return false;
	}
	public function subscribe_jetpack($d, $popup, $validateIp = false) {
		$email = isset($d['email']) ? trim($d['email']) : false;
		if(!empty($email) && is_email($email)) {
			if(!$validateIp || $validateIp && $this->_checkOftenAccess(array('only_check' => true))) {
				if(class_exists('Jetpack')) {
					if(class_exists('Jetpack_Subscriptions')) {
						$name = '';
						if(isset($popup['params']['tpl']['enb_sub_name']) && $popup['params']['tpl']['enb_sub_name']) {
							$name = trim($d['name']);
						}
						$userData = array('email' => $email);
						if(!empty($name)) {
							$firstLastNames = array_map('trim', explode(' ', $name));
							$userData['firstname'] = $firstLastNames[ 0 ];
							if(isset($firstLastNames[ 1 ]) && !empty($firstLastNames[ 1 ])) {
								$userData['lastname'] = $firstLastNames[ 1 ];
							}
						}
						if(isset($popup['params']['tpl']['sub_fields'])
							&& !empty($popup['params']['tpl']['sub_fields'])
						) {
							foreach($popup['params']['tpl']['sub_fields'] as $k => $f) {
								if(in_array($k, array('name', 'email'))) continue;	// Ignore standard fields
								if(isset($d[ $k ])) {
									$userData[$k] = $d[ $k ];
								}
							}
						}
						$jetSubMod = new Jetpack_Subscriptions();
						$jetRes = $jetSubMod->subscribe($email, 0, false, array(
							'source'         => 'supsystic-popup',
							'comment_status' => '',
							'server_data'    => $_SERVER,
							// Not sure - will they parsed or now - as there are no documentation about how put additional fields to Jetpack API. 
							// If you find it - you can change it here.
							'fields'		 => $userData,
						));
						if(!empty($jetRes) && isset($jetRes[ 0 ]) && !empty($jetRes[ 0 ])) {
							$res = $jetRes[ 0 ];
							if(is_object($res) && get_class($res) == 'Jetpack_Error') {
								$errorCodes = $res->get_error_codes();
								$jetErrors = array(
									'invalid_email' => __('Not a valid email address', PPS_LANG_CODE),
									'invalid_post_id' => __('Not a valid post ID', PPS_LANG_CODE),
									'unknown_post_id' => __('Unknown post', PPS_LANG_CODE),
									'not_subscribed' => __('Strange error.  Jetpack servers at WordPress.com could subscribe the email.', PPS_LANG_CODE),
									'disabled' => __('Site owner has disabled subscriptions.', PPS_LANG_CODE),
									'active' => __('Already subscribed.', PPS_LANG_CODE),
									'unknown' => __('Strange error.  Jetpack servers at WordPress.com returned something malformed.', PPS_LANG_CODE),
									'unknown_status' => __('Strange error.  Jetpack servers at WordPress.com returned something I didn\'t understand.', PPS_LANG_CODE),
								);
								foreach($errorCodes as $c) {
									$this->pushError ( isset($jetErrors[ $c ]) ? $jetErrors[ $c ] : __('SOmething is going wrong', PPS_LANG_CODE));
								}
							} else {
								return true;
							}
						} else
							$this->pushError (__('Empty response from Jetpack', PPS_LANG_CODE));
					} else
						$this->pushError (__('Subscriptions module is not activated in Jetpack plugin settings. Activate it before start using this subscribe method.', PPS_LANG_CODE));
				} else
					$this->pushError (__('Can\'t find Jetpack plugin on this server', PPS_LANG_CODE));
			}
		} else
			$this->pushError ($this->_getInvalidEmailMsg($popup), 'email');
		return false;
	}
}