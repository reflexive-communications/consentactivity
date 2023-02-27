{crmScope extensionKey='consentactivity'}
    <div class="consentactivity-container">
        {if $error}
            <div>{ts}Sorry, something went wrong. Please try again later!{/ts}</div>
        {else}
            <div>
                <div>{ts 1=$org_name}You have successfully renewed your GDPR consent at %1!{/ts}</div>
            </div>
        {/if}
    </div>
{/crmScope}
