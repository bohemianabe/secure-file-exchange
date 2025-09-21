// ag: file for all the JS logic for admin view
function validateFirmFormFields(selector, firmAccountUrl) {
    let isValid = true;

    $(selector).each(function (e) {
        const value = $(this).val().trim();

        // Custom validation for firm account URL
        if ($(this).attr('name') === 'accountName' && !/^[a-z0-9]+$/.test(firmAccountUrl)) {
            $(this).addClass('is-invalid');
            isValid = false;
            alert('Firm Account URL must be a single word, lowercase letters and numbers only – no spaces or special characters.');
        }
        // Generic required field validation
        else if (value === '' && $(this).prop('required')) {
            isValid = false;
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    return isValid;
}

// ag: since button is in a modal that isn't visible must pass function to the DOM
$(document).on('click', '#admin-reset-firm-user-password', function (e) {
    const firmUserProfileId = e.currentTarget.getAttribute('data-firm-user-id');
    // e.preventDefault();

    // const button = e.relatedTarget; // Button that triggered the modal
    // const title = button.getAttribute('data-firm-user-id'); // Extract info from data-* attributes

    console.log();

    $.ajax({
        url: '/admin/ajax/reset-firm-user-password',
        method: 'POST',
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
                setTimeout(function () {
                    window.location.reload();
                }, 3000);
            } else {
                // ag: if update failed don't refresh the page. no need.
                window.notyf.error(data.message);
            }
        },
    });
});

jQuery(function ($) {
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
            $tokenFirm = $('#admin-new-firm-token').val();
            $tokenUser = $('#admin-new-user-token').val();
            $tokenFirmUserProfile = $('#admin-new-firm-user-profile-token').val();
            formData.append('firm[_token]', $tokenFirm);
            formData.append('user[_token]', $tokenUser);
            formData.append('firm_user_profile[_token]', $tokenFirmUserProfile);

            $.ajax({
                url: '/admin/ajax/new-firm-submit',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success(data) {
                    if (data.success) {
                        $('#adminAddFirmModal').modal('hide');
                        // ag: window.notyf set globally in root app.js file
                        window.notyf.success(data.message);
                        setTimeout(function () {
                            window.location.reload();
                        }, 4000);
                    } else {
                        $('#adminAddFirmModal').modal('hide');
                        window.notyf.error(data.message);
                        setTimeout(function () {
                            window.location.reload();
                        }, 4000);
                    }
                },
            });
        }
    });

    // ag: logic for the update btn from the firm_view for the firm data
    $('#admin-view-firm-update').on('click', function (e) {
        const firmAccountUrl = $('#accountName').val();
        // ag: add firmAccountUrl as second param so it can validate the input with regex
        tokenFirm = $('#admin-update-firm-token').val();
        firmId = $('#admin-update-firm-id').val();

        formValid = validateFirmFormFields('#admin-view-firm-form input, #admin-view-firm-form select', firmAccountUrl);

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
