<div class="card mt-2" id="view_order_payments_block">
  <div class="card-header">
    <h3 class="card-header-title">{l s='BTCPay information' d='Modules.Btcpay.Global'}</h3>
  </div>

  <div class="card-body">
    <div class="col-md-12 mt-2 mb-4">
      <div class="info-block">
        <div class="row">
          <div class="col-sm text-center">
            <p class="text-muted mb-0"><strong>Invoice</strong></p>
            <a class="configure-link" href="{$server_url}/invoices/{$invoice.id}" target="_blank">{$invoice.id}</a>
          </div>

          <div class="col-sm text-center">
            <p class="text-muted mb-0"><strong>Status</strong></p>
            <p class="mb-0">
                {if $invoice.status == constant('\BTCPayServer\Result\Invoice::STATUS_EXPIRED')}
                  <span class="badge badge-danger">{$invoice.status}</span>
                {elseif $invoice.status == constant('\BTCPayServer\Result\Invoice::STATUS_PROCESSING') OR $invoice.status == constant('\BTCPayServer\Result\Invoice::ADDITIONAL_STATUS_PAID_PARTIAL') or $invoice.status == constant('\BTCPayServer\Result\Invoice::ADDITIONAL_STATUS_PAID_OVER')}
                  <span class="badge badge-warning">{$invoice.status}</span>
                {elseif $invoice.status == constant('\BTCPayServer\Result\Invoice::STATUS_SETTLED')}
                  <span class="badge badge-success">{$invoice.status}</span>
                {else}
                    {$invoice.status}
                {/if}
            </p>
          </div>
        </div>
      </div>
    </div>

    {if not empty($paymentMethods)}
      <nav>
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
        {foreach $paymentMethods as $paymentMethod}
          {if not empty($paymentMethod->getPayments())}
            {assign currencyCode "_"|explode:$paymentMethod.paymentMethod|current}
            <a class="nav-item nav-link{if $paymentMethod@first} active{/if}" id="nav-{$currencyCode|strtolower}-tab" data-toggle="tab" href="#nav-{$currencyCode|strtolower}" role="tab" aria-controls="nav-{$currencyCode|strtolower}" aria-selected="true">{$currencyCode}</a>
          {/if}
        {/foreach}
        </div>
      </nav>
      <div class="tab-content" id="nav-tabContent">
      {foreach $paymentMethods as $paymentMethod}
        {if not empty($paymentMethod->getPayments())}
              {assign currencyCode "_"|explode:$paymentMethod.paymentMethod|current}
              <div class="tab-pane fade{if $paymentMethod@first} show active{/if}" id="nav-{$currencyCode|strtolower}" role="tabpanel" aria-labelledby="nav-{$currencyCode|strtolower}-tab">
                <table id="{$currencyCode}-details" class="table table-bordered my-2">
                  <thead>
                  <tr>
                    <th class="table-head-rate">{l s='Rate' d='Modules.Btcpay.Global'}</th>
                    <th class="table-head-cart-amount">{l s='Invoice amount' d='Modules.Btcpay.Global'}</th>
                    <th class="table-head-paid-amount">{l s='Total amount paid in %s' sprintf=[$currencyCode] d='Modules.Btcpay.Global'}</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>{$storeCurrency} {$paymentMethod.rate}</td>
                    <td>{$paymentMethod.amount} {$paymentMethod.paymentMethod}</td>
                    <td>{$paymentMethod.paymentMethodPaid} {$paymentMethod.paymentMethod}</td>
                  </tr>
                  </tbody>
                </table>
                <table id="{$currencyCode}-payments" class="table table-bordered my-2">
                  <thead>
                  <tr>
                    <th class="table-head-date">{l s='Date' d='Modules.Btcpay.Global'}</th>
                    <th class="table-head-amount">{l s='Amount' d='Modules.Btcpay.Global'}</th>
                    <th class="table-head-destination">{l s='Transaction' d='Modules.Btcpay.Global'}</th>
                  </tr>
                  </thead>
                  <tbody>
                  {foreach $paymentMethod->getPayments() as $payment}
                    <tr>
                      <td>{$payment->getReceivedTimestamp()|date_format:"%Y-%m-%d %T"}</td>
                      <td>{$payment.value} {$currencyCode}</td>
                        {if $currencyCode == 'BTC'}
                          <td><a href="https://mempool.space/tx/{$payment->getTransactionId()}" target="_blank">{$payment->getTransactionId()}</a></td>
                        {else}
                          <td><a href="https://blockchair.com/search?q={$payment->getTransactionId()}" target="_blank">{$payment->getTransactionId()}</a></td>
                        {/if}
                    </tr>
                  {/foreach}
                  </tbody>
                </table>
              </div>
            {/if}
      {/foreach}
      </div>
    {/if}
  </div>
</div>
