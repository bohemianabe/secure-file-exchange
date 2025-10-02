import * as bootstrap from 'bootstrap';

// ag: file for all the JS logic for admin view
function validateFormFields(selector, firmAccountUrl = null) {
    let isValid = true;

    $(selector).each(function (e) {
        const value = $(this).val().trim();
        const fieldName = $(this).attr('name');
        const fieldType = $(this).attr('type');

        // Custom validation for firm account URL
        if (firmAccountUrl != null) {
            if ($(this).attr('name') === 'accountName' && !/^[a-z0-9]+$/.test(firmAccountUrl)) {
                $(this).addClass('is-invalid');
                isValid = false;
                alert('Firm Account URL must be a single word, lowercase letters and numbers only – no spaces or special characters.');
            }
        }

        // Generic required field validation
        if (value === '' && $(this).prop('required')) {
            isValid = false;
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }

        // --- Email validation ---
        if ((fieldType === 'email' || fieldName === 'email') && value !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                $(this).addClass('is-invalid');
                alert('Please enter a valid email address.');
            } else {
                $(this).removeClass('is-invalid');
            }
        }
    });

    return isValid;
}

// ag: function to handle returned ajax data
function handleAjaxReturnedData(data, btnObject) {
    let modal = btnObject.closest('.modal');
    let modalInstance = bootstrap.Modal.getInstance(modal[0]);

    if (data.success == true) {
        // ag: if successful clear out form, don't do it if it fails
        let form = modal.find('form')[0];
        if (form) {
            form.reset();
        }

        // optional: also clear file input preview if you have one
        $(modal).find('input[type="file"]').val('');
        // bootstrap 5 API: hide modal
        if (modalInstance) {
            modalInstance.hide();
        }
        // ag: window.notyf set globally in root app.js file
        window.notyf.success(data.message);
        setTimeout(function () {
            window.location.reload();
        }, 4000);
    } else {
        // ag: if fail don't close modal. keep open for users descretion
        // if (modalInstance) {
        //     modalInstance.hide();
        // }
        window.notyf.error(data.message);
    }
}

if (document.getElementById('admin-clear-firm-csv-input')) {
    document.getElementById('admin-clear-firm-csv-input').addEventListener('click', function () {
        const fileInput = document.getElementById('admin-firm-csv-input');
        fileInput.value = ''; // reset the input
    });
}

if (document.getElementById('clear-file-input')) {
    document.getElementById('clear-file-input').addEventListener('click', function () {
        const fileInput = document.getElementById('file-input');
        fileInput.value = ''; // reset the input
    });
}

// ag: since button is in a modal that isn't visible must pass function to the DOM
$(document).on('click', '#admin-reset-firm-user-password', function (e) {
    const firmUserProfileId = e.currentTarget.getAttribute('data-firm-user-id');

    $.ajax({
        url: `/admin/ajax/reset-firm-user-password/`,
        METHOD: 'POST',
        data: {
            firmUserProfileId: firmUserProfileId,
        },
        // processData: false,
        // contentType: false,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        success(data) {
            if (data.success) {
                // ag: window.notyf set globally in root app.js file
                window.notyf.success(data.message);
            } else {
                // ag: if update failed don't refresh the page. no need.
                window.notyf.error(data.message);
            }
        },
    });
});

jQuery(function ($) {
    $('.checkbox-toggle').on('change', function () {
        $(this).val($(this).is(':checked') ? '1' : '0');
    });

    // ag: this resets ALL modals to empty fields when closed
    $(document).on('hidden.bs.modal', '.modal', function () {
        // Reset all form fields inside the modal
        $(this)
            .find('form')
            .each(function () {
                this.reset(); // native reset
            });

        // Remove validation classes if you use them
        $(this).find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');

        // Make sure checkboxes and radios are reset too
        // $(this).find('input:checkbox, input:radio').prop('checked', false);
    });

    // ag: logic for the add new firm/firmuser modal on dashboard page
    $('.admin-add-primary-user').on('click', function (e) {
        e.preventDefault();

        const firmAccountUrl = $('#accountName').val();
        let isValidForm1 = true;

        $('#form-part-1 input, #form-part-1 select').each(function (e) {
            const value = $(this).val().trim();
            // ag: check if accountName is one word all lowercase
            if ($(this).attr('name') == 'accountName' && !/^[a-z0-9]+$/.test(firmAccountUrl)) {
                $(this).addClass('is-invalid');
                isValidForm1 = false;
                alert('Firm Account URL must be a single word, lowercase letters and numbers only — no spaces or special characters.');
            } else if (value === '' && $(this).prop('required')) {
                isValidForm1 = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (isValidForm1) {
            // ag: hide form1 and change modal header
            $('#form-part-1').addClass('d-none');
            $('#form-part-2').removeClass('d-none');
            $('#adminAddFirmModalLabel').text('Add Primary User');

            //ag: display buttons for form2
            $('#admin-new-firm-return-to-form1').removeClass('d-none');
            $('#admin-new-firm-submit').removeClass('d-none');
            $('#admin-add-primary-user').addClass('d-none');
        }
    });

    // ag: logic for the back button on add new firm modal
    $('#admin-new-firm-return-to-form1').on('click', function (e) {
        $('#form-part-1').removeClass('d-none');
        $('#form-part-2').addClass('d-none');
        $('#adminAddFirmModalLabel').text('Add New Firm');

        //ag: display buttons for form2
        $('#admin-new-firm-return-to-form1').addClass('d-none');
        $('#admin-new-firm-submit').addClass('d-none');
        $('#admin-add-primary-user').removeClass('d-none');
    });

    // ag: logic to handle submit of a new firm modal
    $('#admin-new-firm-submit').on('click', function (e) {
        let btn = $(this);

        // ag: set spinner on submit button
        btn.find('.btn-text').addClass('d-none');
        btn.find('.spinner-border').removeClass('d-none');
        btn.prop('disabled', true);

        let isValidForm2 = true;
        $('#form-part-2 input').each(function (e) {
            const value = $(this).val().trim();
            const type = $(this).attr('type');

            if (value === '' && $(this).prop('required')) {
                isValidForm2 = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }

            // Optional: validate email format if this input is type="email"
            if (type === 'email') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValidForm2 = false;
                    $(this).addClass('is-invalid');
                    alert('Please make sure you enter a valid email formatted address.');
                }
            }
        });

        if (isValidForm2) {
            const firmForm = document.querySelector('#form-part-1');
            const firmUserProfileForm = document.querySelector('#form-part-2');

            const formData = new FormData();

            // Append firm form values
            [...new FormData(firmForm).entries()].forEach(([key, value]) => {
                formData.append(`firm[${key}]`, value);
            });

            // ag: datapoints for the User entity
            const userArray = ['roles', 'email'];
            // Append user form values
            [...new FormData(firmUserProfileForm).entries()].forEach(([key, value]) => {
                if (userArray.includes(key)) {
                    if (key == 'roles') {
                        formData.append('user[roles][]', value);
                    } else {
                        formData.append(`user[${key}]`, value);
                    }
                } else {
                    formData.append(`firm_user_profile[${key}]`, value);
                }
            });

            // ag: add the csrf token for each form
            const tokenFirm = $('#admin-new-firm-token').val();
            const tokenUser = $('#admin-new-user-token').val();
            const tokenFirmUserProfile = $('#admin-new-firm-user-profile-token').val();
            formData.append('firm[_token]', tokenFirm);
            formData.append('user[_token]', tokenUser);
            formData.append('firm_user_profile[_token]', tokenFirmUserProfile);

            $.ajax({
                url: '/admin/ajax/new-firm-submit',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success(data) {
                    handleAjaxReturnedData(data, btn);
                },
                complete: function () {
                    // Restore button
                    btn.find('.btn-text').removeClass('d-none');
                    btn.find('.spinner-border').addClass('d-none');
                    btn.prop('disabled', false);
                },
            });
        }
    });

    // ag: logic to add a firm user profile on the /admin/firm-view/{id} page
    $('#admin-add-firm-user-submit').on('click', function (e) {
        e.preventDefault();

        let formValid = validateFormFields('#admin-add-firm-user input, #admin-add-firm-user select', null);

        if (formValid) {
            let btn = $(this);

            // ag: set spinner on submit button
            btn.find('.btn-text').addClass('d-none');
            btn.find('.spinner-border').removeClass('d-none');
            btn.prop('disabled', true);

            const firmUserProfileForm = document.querySelector('#admin-add-firm-user');

            const formData = new FormData();

            // ag: datapoints for the User entity
            const userArray = ['roles', 'email'];

            // ag: if the checked boxes aren't checked insert them into the form as '0' otherwise they won't show at all in the forEach below
            firmUserProfileForm.querySelectorAll('input[type="checkbox"]').forEach((field) => {
                if (!field.checked) {
                    formData.append(`firm_user_profile[${field.name}]`, '0');
                }
            });

            // Append user form values
            [...new FormData(firmUserProfileForm).entries()].forEach(([key, value]) => {
                if (userArray.includes(key)) {
                    if (key == 'roles') {
                        formData.append('user[roles][]', value);
                    } else {
                        formData.append(`user[${key}]`, value);
                    }
                } else {
                    formData.append(`firm_user_profile[${key}]`, value);
                }
            });

            const tokenUser = $('#admin-add-firm-user-profile-token').val();
            const tokenFirmUserProfile = $('#admin-add-user-profile-token').val();
            const firmId = $('#admin-add-user-firm-id').val();
            formData.append('user[_token]', tokenUser);
            formData.append('firm_user_profile[_token]', tokenFirmUserProfile);

            $.ajax({
                url: '/admin/ajax/' + firmId + '/add-new-firm-user',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success(data) {
                    handleAjaxReturnedData(data, btn);
                },
                complete: function () {
                    // Restore button
                    btn.find('.btn-text').removeClass('d-none');
                    btn.find('.spinner-border').addClass('d-none');
                    btn.prop('disabled', false);
                },
            });
        }
    });

    $('#admin-import-firm-csv-submit').on('click', function (e) {
        e.preventDefault();
        let btn = $(this);

        // ag: set spinner on submit button
        btn.find('.btn-text').addClass('d-none');
        btn.find('.spinner-border').removeClass('d-none');
        btn.prop('disabled', true);

        const formData = new FormData();
        const fileInput = document.getElementById('admin-firm-csv-input');

        formData.append('file', fileInput.files[0]);

        $.ajax({
            url: '/admin/firm-import-csv/submit',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success(data) {
                handleAjaxReturnedData(data, btn);
            },
            complete: function () {
                // Restore button
                btn.find('.btn-text').removeClass('d-none');
                btn.find('.spinner-border').addClass('d-none');
                btn.prop('disabled', false);
            },
        });
    });

    // ag: logic for the update btn from the firm_view for the firm data
    $('#admin-view-firm-update').on('click', function (e) {
        const firmAccountUrl = $('#accountName').val();
        // ag: add firmAccountUrl as second param so it can validate the input with regex
        tokenFirm = $('#admin-update-firm-token').val();
        firmId = $('#admin-update-firm-id').val();

        formValid = validateFormFields('#admin-view-firm-form input, #admin-view-firm-form select', firmAccountUrl);

        const firmForm = document.querySelector('#admin-view-firm-form');

        const formData = new FormData();

        // Append firm form values
        [...new FormData(firmForm).entries()].forEach(([key, value]) => {
            formData.append(`firm[${key}]`, value);
        });

        formData.append('firm[_token]', tokenFirm);
        formData.append('firmId', firmId);

        if (formValid) {
            $.ajax({
                url: '/admin/ajax/update-firm-data',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success(data) {
                    if (data.success) {
                        // ag: window.notyf set globally in root app.js file
                        window.notyf.success(data.message);
                        setTimeout(function () {
                            window.location.reload();
                        }, 3000);
                    } else {
                        // ag: if update failed don't refresh the page. no need.
                        window.notyf.error(data.message);
                    }
                },
            });
        }
    });

    // ag: dynamic firm active/deactive logic
    const adminFirmActiveToggleModal = document.getElementById('adminToggleFirmStatusModal');

    if (adminFirmActiveToggleModal) {
        adminFirmActiveToggleModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const title = button.getAttribute('data-modal-title'); // Extract info from data-* attributes

            const modalTitle = adminFirmActiveToggleModal.querySelector('.modal-title');
            modalTitle.textContent = title || 'Default Title';
        });
    }

    // *************************************************** DATATABLE LOGIC ***************************************************** //
    $('#adminDashboardViewTable').DataTable({
        pageLength: 10,
        order: [[0, 'asc']], // default sort first column
    });

    $('#adminFirmViewTable').DataTable({
        rowCallback: function (row, data) {
            // assuming "User Type" is column index 5 (0-based index, adjust as needed)
            if (data[5] === 'primary') {
                $(row).addClass('table-success primary-row');
            }
        },
    });
}); // ag: end of jQuery
