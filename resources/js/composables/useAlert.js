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
            text: message,
            iconColor: '#4CAF8E'
        });
    };

    const error = (title, message = '') => {
        Toast.fire({
            icon: 'error',
            title: title,
            text: message,
            iconColor: '#E5533D'
        });
    };

    const warning = (title, message = '') => {
        Toast.fire({
            icon: 'warning',
            title: title,
            text: message,
            iconColor: '#F59E0B'
        });
    };

    const confirm = async (title, text, confirmButtonText = 'ตกลง') => {
        const result = await Swal.fire({
            title: title,
            text: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4CAF8E', 
            cancelButtonColor: '#E5533D',  
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'ยกเลิก',
            background: '#F6F5F2', 
            color: '#000000',     
            iconColor: '#6B4F3F'
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
