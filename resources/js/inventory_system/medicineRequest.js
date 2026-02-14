window.addEventListener('medicineRequest-added', event => {
    Swal.fire({
        title: "Success!",
        text: "Medicine has been added successfully.",
        icon: "success",
        showConfirmButton: false,
        timer:1500
    });

    setTimeout(() => {
        window.location.reload();
    }, 1000);
});


document.addEventListener('DOMContentLoaded', function () {

    // Show Edit Modal
    window.addEventListener('show-editRequest-modal', event => {
        let modal = new bootstrap.Modal(document.getElementById('editMedicineModal'));
        modal.show();
    });

    // Hide Edit Modal
    window.addEventListener('close-medicineRequest-modal', event => {
        Swal.fire({
            title: "Success!",
            text: "Medicine has been edited successfully.",
            icon: "success",
            showConfirmButton: false,
            timer:1500
        }).then(() => {
            let modalEl = document.getElementById('editMedicineModal');
            let modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
        })
    });


    // show medicine delete modal
    window.addEventListener('show-deleleteRequestModal', () => {
        Swal.fire({
            title: "Are you sure?",
            text: "This category will be permanently deleted!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, delete it!",
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'))
                .deleteRequest();

            }
        });
    });

    // Listen to success event
    window.addEventListener('success-deleteMedicineRequestModal', () => {
        Swal.fire({
            title: "Deleted!",
            text: "Request Medicine has been deleted.",
            icon: "success",
            timer: 1500,
            showConfirmButton: false
        });
    });
});
