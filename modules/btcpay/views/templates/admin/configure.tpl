<div style="width: 100%; height: 125px;">
  <div style="float: right; border: dashed 1px #666; padding: 8px;">
    <a href="{$btcpayurl}" target="_blank" rel="nofollow">
      <img src="{$module_dir}prestashop_btcpay.png" alt="PrestaShop & BTCPay"/>
    </a>
  </div>
  <img src="{$module_dir}btcpay-plugin.png" alt="PrestaShop & BTCPay" style="float:left; margin-right:15px;"/>
  <p><strong>{l s='This module allows you to accept payments by BTCPay.' mod='btcpay'}</strong></p>
  <p>{l s='You need to configure your BtcPay account before using this module.' mod='btcpay'}</p>
</div>

<form method="post" action="{$smarty.server.REQUEST_URI}">
  <div class="row ps17">
    <div class="col-lg-12">
      <div class="row">
        <div class="panel">
          <div class="panel-heading">
            <i class="ps-icon ps-icon-broken-link icon icon-chain-broken"></i>
            {l s='Settings' mod='btcpay'}
          </div>
          <div class="panel-body">
            <div class="form-group">
              <label for="form_btcpay_url">{l s='BTCPAY Server URL' mod='btcpay'}</label>
              <input type="text" class="form-control" name="form_btcpay_url" aria-describedby="emailHelp" placeholder="BTCPay Url (eg. {$btcpayurl})" value="{$formBTCPayURL}" required>
            </div>

            <div class="form-group">
              <label for="form_btcpay_txspeed">{l s='Transaction Speed' mod='btcpay'}</label>
              <select class="form-control" name="form_btcpay_txspeed" required>
                <option value="low" {if $txSpeed == 'low'}selected="selected"{/if}>Low</option>
                <option value="medium" {if $txSpeed == 'medium'}selected="selected"{/if}>Medium</option>
                <option value="high" {if $txSpeed == 'high'}selected="selected"{/if}>High</option>
              </select>
            </div>

            <div class="form-group">
              <label for="form_btcpay_ordermode">{l s='Order Mode' mod='btcpay'}</label>
              <select class="form-control" name="form_btcpay_ordermode" required>
                <option value="beforepayment" {if $txSpeed == 'beforepayment'}selected="selected"{/if}>Order before payment</option>
                <option value="afterpayment" {if $txSpeed == 'afterpayment'}selected="selected"{/if}>Order after payment</option>
              </select>
            </div>

            <div class="form-group">
              <label for="form_btcpay_pairingcode">{l s='Pairing Code' mod='btcpay'}</label>
              <input type="text" class="form-control" name="form_btcpay_pairingcode" aria-describedby="pairingCodeHelp" value="{$formPairingCode}" required>
              <small id="pairingCodeHelp" class="form-text text-muted">Get a pairing code at: <a href="{$btcpayurl}/api-tokens" target="_blank">{$btcpayurl}/api-tokens</a>.</small>
            </div>
          </div>
          <div class="panel-footer">
            <fieldset>
              <div class="form-group">
                <button class="btn btn-default button pull-right" type="submit" name="submitpairing">
                  <i class="process-icon-save"></i>Save
                </button>
              </div>
            </fieldset>
          </div>
        </div>
      </div>
    </div>
</form>
