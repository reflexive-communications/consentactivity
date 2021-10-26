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
            <td class="label">{$form.consentExpirationTaggingDays.label}</td>
            <td class="content">{$form.consentExpirationTaggingDays.html}<br/>
                <span class="description">{ts}The tag will applied on the contact before this days of the expiration date.{/ts}</span>
            </td>
        </tr>
    </table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>