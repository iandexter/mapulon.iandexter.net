<form id="encryption">
	<fieldset class="personalblock">

		<p>
			<strong><?php p($l->t('Encryption')); ?></strong>
			<br/>
		</p>

		<p>
			<?php p($l->t("Enable encryption passwords recovery key (allow sharing to recovery key):")); ?>
			<br/>
			<br/>
			<input type="password" name="recoveryPassword" id="recoveryPassword"/>
			<label for="recoveryPassword"><?php p($l->t("Recovery account password")); ?></label>
			<br/>
			<input
				type='radio'
				name='adminEnableRecovery'
				value='1'
				<?php echo($_["recoveryEnabled"] == 1 ? 'checked="checked"' : 'disabled'); ?> />
			<?php p($l->t("Enabled")); ?>
			<br/>

			<input
				type='radio'
				name='adminEnableRecovery'
				value='0'
				<?php echo($_["recoveryEnabled"] == 0 ? 'checked="checked"' : 'disabled'); ?> />
			<?php p($l->t("Disabled")); ?>
		</p>
		<br/><br/>

		<p>
			<strong><?php p($l->t("Change encryption passwords recovery key:")); ?></strong>
			<br/><br/>
			<input
				type="password"
				name="changeRecoveryPassword"
				id="oldRecoveryPassword"
				<?php echo($_["recoveryEnabled"] == 0 ? 'disabled' : ''); ?> />
			<label for="oldRecoveryPassword"><?php p($l->t("Old Recovery account password")); ?></label>
			<br/>
			<input
				type="password"
				name="changeRecoveryPassword"
				id="newRecoveryPassword"
				<?php echo($_["recoveryEnabled"] == 0 ? 'disabled' : ''); ?> />
			<label for="newRecoveryPassword"><?php p($l->t("New Recovery account password")); ?></label>
			<br/>
			<button
				type="button"
				name="submitChangeRecoveryKey"
				disabled><?php p($l->t("Change Password")); ?>
			</button>
			<span class="msg"></span>
		</p>
	</fieldset>
</form>
