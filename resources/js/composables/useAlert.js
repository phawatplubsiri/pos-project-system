import Swal from 'sweetalert2';

// Create a base Swal instance with our custom classes so we can style it via CSS
const BaseSwal = Swal.mixin({
    customClass: {
        popup: 'swal2-popup lp-popup',
        title: 'lp-title',
        htmlContainer: 'lp-content',
        confirmButton: 'lp-btn-confirm',
        cancelButton: 'lp-btn-cancel'
    },
    buttonsStyling: false,
    showClass: {
        popup: 'swal2-show'
    },
    hideClass: {
        popup: 'swal2-hide'
    }
});

export function useAlert() {
    // Toast configuration for small notifications (based on BaseSwal so it inherits customClass)
    const Toast = BaseSwal.mixin({
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
        const result = await BaseSwal.fire({
            title: title,
            html: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'ยกเลิก',
            background: '#ffffff',
            iconColor: '#2F9D7E'
        });
        return result.isConfirmed;
    };

    const loading = (title = 'กำลังประมวลผล...') => {
        BaseSwal.fire({
            title: title,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    };

    const close = () => {
        BaseSwal.close();
    };

    const showPin = async (title, pin) => {
        await BaseSwal.fire({
            title: title,
            html: `<div class="swal-pin"><div class="swal-pin-num">${pin}</div><div class="swal-pin-note">เก็บ PIN นี้ไว้และแจ้งพนักงาน</div></div>`,
            icon: 'success',
            showCancelButton: false,
            showConfirmButton: true,
            confirmButtonText: 'ปิด',
            allowOutsideClick: false,
            background: '#ffffff',
        });
    };

    return {
        success,
        error,
        warning,
        confirm,
        loading,
        close,
        showPin,
        Toast
    };
}
