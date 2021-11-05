<section class="mb-2">
  <p class="mb-1">{l s='Please pay the exact amount (including transaction fees when paying on-chain).' d='Modules.Btcpay.Front'}</p>
  <hr class="mb-1"/>
  <p class="mb-1"><strong>{l s='Supported payment methods' d='Modules.Btcpay.Front'}</strong>:</p>
  <dl>
      {foreach $offchain as $paymentMethod}
        <dt>{$paymentMethod.cryptoCode} Lightning ⚡</dt>
      {/foreach}
      {foreach $onchain as $paymentMethod}
        <dt>{$paymentMethod.cryptoCode} (On-Chain)</dt>
      {/foreach}
  </dl>
</section>
