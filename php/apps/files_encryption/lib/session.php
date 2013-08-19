<?php
/**
 * ownCloud
 *
 * @author Sam Tuke
 * @copyright 2012 Sam Tuke samtuke@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Encryption;

/**
 * Class for handling encryption related session data
 */

class Session {

	private $view;

	/**
	 * @brief if session is started, check if ownCloud key pair is set up, if not create it
	 * @param \OC_FilesystemView $view
	 *
	 * @note The ownCloud key pair is used to allow public link sharing even if encryption is enabled
	 */
	public function __construct($view) {

		$this->view = $view;

		if (!$this->view->is_dir('owncloud_private_key')) {

			$this->view->mkdir('owncloud_private_key');

		}

		$publicShareKeyId = \OC_Appconfig::getValue('files_encryption', 'publicShareKeyId');

		if ($publicShareKeyId === null) {
			$publicShareKeyId = 'pubShare_' . substr(md5(time()), 0, 8);
			\OC_Appconfig::setValue('files_encryption', 'publicShareKeyId', $publicShareKeyId);
		}

		if (
			!$this->view->file_exists("/public-keys/" . $publicShareKeyId . ".public.key")
			|| !$this->view->file_exists("/owncloud_private_key/" . $publicShareKeyId . ".private.key")
		) {

			$keypair = Crypt::createKeypair();

			// Disable encryption proxy to prevent recursive calls
			$proxyStatus = \OC_FileProxy::$enabled;
			\OC_FileProxy::$enabled = false;

			// Save public key

			if (!$view->is_dir('/public-keys')) {
				$view->mkdir('/public-keys');
			}

			$this->view->file_put_contents('/public-keys/' . $publicShareKeyId . '.public.key', $keypair['publicKey']);

			// Encrypt private key empty passphrase
			$encryptedPrivateKey = Crypt::symmetricEncryptFileContent($keypair['privateKey'], '');

			// Save private key
			$this->view->file_put_contents(
				'/owncloud_private_key/' . $publicShareKeyId . '.private.key', $encryptedPrivateKey);

			\OC_FileProxy::$enabled = $proxyStatus;

		}

		if (\OCA\Encryption\Helper::isPublicAccess()) {
			// Disable encryption proxy to prevent recursive calls
			$proxyStatus = \OC_FileProxy::$enabled;
			\OC_FileProxy::$enabled = false;

			$encryptedKey = $this->view->file_get_contents(
				'/owncloud_private_key/' . $publicShareKeyId . '.private.key');

			$privateKey = Crypt::decryptPrivateKey($encryptedKey, '');

			$this->setPublicSharePrivateKey($privateKey);

			\OC_FileProxy::$enabled = $proxyStatus;
		}
	}

	/**
	 * @brief Sets user private key to session
	 * @param string $privateKey
	 * @return bool
	 *
	 * @note this should only be set on login
	 */
	public function setPrivateKey($privateKey) {

		$_SESSION['privateKey'] = $privateKey;

		return true;

	}

	/**
	 * @brief Gets user or public share private key from session
	 * @returns string $privateKey The user's plaintext private key
	 *
	 */
	public function getPrivateKey() {

		// return the public share private key if this is a public access
		if (\OCA\Encryption\Helper::isPublicAccess()) {
			return $this->getPublicSharePrivateKey();
		} else {

			if (isset($_SESSION['privateKey']) && !empty($_SESSION['privateKey'])) {
				return $_SESSION['privateKey'];
			} else {
				return false;
			}
		}
	}

	/**
	 * @brief Sets public user private key to session
	 * @param string $privateKey
	 * @return bool
	 */
	public function setPublicSharePrivateKey($privateKey) {

		$_SESSION['publicSharePrivateKey'] = $privateKey;

		return true;

	}

	/**
	 * @brief Gets public share private key from session
	 * @returns string $privateKey
	 *
	 */
	public function getPublicSharePrivateKey() {

		if (isset($_SESSION['publicSharePrivateKey']) && !empty($_SESSION['publicSharePrivateKey'])) {
			return $_SESSION['publicSharePrivateKey'];
		} else {
			return false;
		}

	}


	/**
	 * @brief Sets user legacy key to session
	 * @param $legacyKey
	 * @return bool
	 */
	public function setLegacyKey($legacyKey) {

		$_SESSION['legacyKey'] = $legacyKey;

		return true;
	}

	/**
	 * @brief Gets user legacy key from session
	 * @returns string $legacyKey The user's plaintext legacy key
	 *
	 */
	public function getLegacyKey() {

		if (
			isset($_SESSION['legacyKey'])
			&& !empty($_SESSION['legacyKey'])
		) {

			return $_SESSION['legacyKey'];

		} else {

			return false;

		}

	}

}
