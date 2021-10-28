/*
 * Event listener for the new parameter mapping button. It creates
 * a copy from the first row makes the values empty and appends it
 * before the button. The name, id of the fields has to be also 
 * updated to a uniq value.
 */
function mapParameterMappingHandler(event) {
    let newRow = document.querySelector('#parameter-mapping table tr:nth-of-type(2)').cloneNode(true);
    let numberOfItems = document.querySelectorAll('#parameter-mapping table tr').length - 2;
    newRow.querySelectorAll("select [name*='map_custom_field_id_']").forEach(function(element) {element.value = '0'; element.name = 'map_custom_field_id_'+numberOfItems; element.id = 'map_custom_field_id_'+numberOfItems;});
    newRow.querySelectorAll("select [name*='map_consent_field_id_']").forEach(function(element) {element.value = '0'; element.name = 'map_consent_field_id_'+numberOfItems; element.id = 'map_consent_field_id_'+numberOfItems;});
    newRow.querySelectorAll("select [name*='map_group_id_']").forEach(function(element) {element.value = '0'; element.name = 'map_group_id_'+numberOfItems; element.id = 'map_group_id_'+numberOfItems;});
    let buttonRow = document.querySelector('#new-parameter-mapping-row');
    buttonRow.parentNode.insertBefore(newRow, buttonRow);
};
(function() {
    document.querySelector('#new-parameter-mapping').addEventListener('click', mapParameterMappingHandler);
})();
