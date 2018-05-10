<fieldset style="width: 140px; margin-right: 300px;">

<legend>

     {l s='BtcPay Information' mod='btcpay'}

</legend>

<div id="info">
	<table>
	<tr>
		<td align="left" valign="top">{l s='Invoice:' mod='btcpay'}</td>
		<td><a href="{$btcpayurl}/invoice?id={$invoice_id}" title="" target="_blank">Open</a></td>
	</tr>
	<tr>
		<td align="left" valign="top">{l s='Status:' mod='btcpay'}</td>
		<td>{$status}</td>
	</tr>
	</table>
</div>

</fieldset>
