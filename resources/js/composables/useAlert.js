import Swal from 'sweetalert2';

export function useAlert() {
    // Toast configuration for small notifications
    const Toast = Swal.mixin({
        toast: true,
        position: 'bottom-start',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    const success = (title, message = '') => {
        Toast.fire({
            icon: 'success',
            title: title,
            text: message
        });
    };

    const error = (title, message = '') => {
        Toast.fire({
            icon: 'error',
            title: title,
            text: message
        });
    };

    const warning = (title, message = '') => {
        Toast.fire({
            icon: 'warning',
            title: title,
            text: message
        });
    };

    const confirm = async (title, text, confirmButtonText = 'ตกลง') => {
        const result = await Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4CAF8E', // --color-action
            cancelButtonColor: '#E5533D',  // --color-danger
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'ยกเลิก',
            background: '#F6F5F2', // --color-bg-primary
            color: '#2E2E2E',     // --color-text-primary
        });
        return result.isConfirmed;
    };

    const loading = (title = 'กำลังประมวลผล...') => {
        Swal.fire({
            title: title,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    };

    const close = () => {
        Swal.close();
    };

    return {
        success,
        error,
        warning,
        confirm,
        loading,
        close,
        Toast
    };
}
