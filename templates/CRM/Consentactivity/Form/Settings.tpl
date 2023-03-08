<div class="crm-block crm-form-block">
    <table class="form-layout">
        <tr>
            <td class="label">{$form.consentExpirationYears.label}</td>
            <td class="content">{$form.consentExpirationYears.html}<br/>
                <span class="description">{ts}The contacts will marked as expired after this years since the last consent activity.{/ts}</span>
            </td>
        </tr>
        <tr>
            <td class="label">{$form.tagId.label}</td>
            <td class="content">{$form.tagId.html}<br/>
                <span class="description">{ts}This tag will applied on the contact on the tagging action.{/ts}</span>
            </td>
        </tr>
        <tr>
            <td class="label">{$form.expiredTagId.label}</td>
            <td class="content">{$form.expiredTagId.html}<br/>
                <span class="description">{ts}This tag will applied on the contact on the anonymization action.{/ts}</span>
            </td>
        </tr>
        <tr>
            <td class="label">{$form.consentExpirationTaggingDays.label}</td>
            <td class="content">{$form.consentExpirationTaggingDays.html}<br/>
                <span class="description">{ts}The tag will applied on the contact before this days of the expiration date.{/ts}</span>
            </td>
        </tr>
        <tr>
            <td class="label">{$form.consentAfterContribution.label}</td>
            <td class="content">{$form.consentAfterContribution.html}<br/>
                <span class="description">{ts}Add a consent activity after contribution.{/ts}</span>
            </td>
        </tr>
    </table>
</div>
<div id="parameter-mapping">
    <h3>{ts}Parameter mapping{/ts}</h3>
    <table id="parameter-mapping-table">
        <tr>
            <th>{ts}Custom Field{/ts}</th>
            <th>{ts}Consent Field{/ts}</th>
            <th>{ts}Group{/ts}</th>
        </tr>
        {foreach from=$cfMap key=customFieldId item=mappedField}
            {assign var="consentName" value=$mappedField.consent}
            {assign var="groupName" value=$mappedField.group}
            <tr>
                <td>{$form.$customFieldId.html}</td>
                <td>{$form.$consentName.html}</td>
                <td>{$form.$groupName.html}</td>
            </tr>
        {/foreach}
        <tr id="new-parameter-mapping-row">
            <td colspan="3">
                <button type="button" class="ui-button ui-corner-all ui-widget" id="new-parameter-mapping">{icon icon="fa-plus-circle"}{/icon}</button>
            </td>
        </tr>
    </table>
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
