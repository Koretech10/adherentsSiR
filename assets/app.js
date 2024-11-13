/** Ce fichier n'est utilisé uniquement que pour charger l'AssetMapper **/

// Manage batch actions using the data-action-no-modal attribute.
// @see vendor/easycorp/easyadmin-bundle/assets/js/app.js.
document.querySelectorAll('[data-action-no-modal]').forEach((dataActionBatch) => {
    dataActionBatch.addEventListener('click', (event) => {
        event.preventDefault();

        const actionElement = event.target.tagName.toUpperCase() === 'A' ? event.target : event.target.parentNode;
        const selectedItems = document.querySelectorAll('input[type="checkbox"].form-batch-checkbox:checked');

        // prevent double submission of the batch action form
        actionElement.setAttribute('disabled', 'disabled');

        const batchFormFields = {
            'batchActionName': actionElement.getAttribute('data-action-name'),
            'entityFqcn': actionElement.getAttribute('data-entity-fqcn'),
            'batchActionUrl': actionElement.getAttribute('data-action-url'),
            'batchActionCsrfToken': actionElement.getAttribute('data-action-csrf-token'),
        };
        selectedItems.forEach((item, i) => {
            batchFormFields[`batchActionEntityIds[${i}]`] = item.value;
        });

        const batchForm = document.createElement('form');
        batchForm.setAttribute('method', 'POST');
        batchForm.setAttribute('action', actionElement.getAttribute('data-action-url'));
        for (let fieldName in batchFormFields) {
            const formField = document.createElement('input');
            formField.setAttribute('type', 'hidden');
            formField.setAttribute('name', fieldName);
            formField.setAttribute('value', batchFormFields[fieldName]);
            batchForm.appendChild(formField);
        }

        document.body.appendChild(batchForm);
        batchForm.submit();
    });
});