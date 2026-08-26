import '../css/app.css';

const initProfilePhoto = () => {
    const translations = window.profileTranslations || {};
    const translate = (key, replacements = {}) => Object.entries(replacements).reduce((message, [name, value]) => message.replace(`:${name}`, value), translations[key] || key);
    const input = document.getElementById('profile_photo');
    const preview = document.getElementById('photoPreviewImg');
    const fallback = document.getElementById('photoPreviewFallback');
    const note = document.getElementById('photoPreviewNote');
    const openCamera = document.getElementById('openCamera');
    const modal = document.getElementById('cameraModal');
    const video = document.getElementById('cameraVideo');
    const message = document.getElementById('cameraMessage');
    const capturePhoto = document.getElementById('capturePhoto');
    const closeCamera = document.getElementById('closeCamera');

    if (!input || !preview) return;

    const defaultNote = note?.textContent ?? '';

    const showPreview = (file) => {
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
        if (fallback) fallback.style.display = 'none';
        if (note) note.textContent = translate('selected', { name: file.name });
    };

    input.addEventListener('change', () => {
        const file = input.files?.[0];

        if (!file) {
            if (note) note.textContent = defaultNote;
            return;
        }

        if (!file.type.startsWith('image/')) {
            if (note) note.textContent = translate('notImage');
            input.value = '';
            return;
        }

        showPreview(file);
    });

    if (openCamera && modal && video && message && capturePhoto && closeCamera) {
        let stream;

        const stopCamera = () => {
            stream?.getTracks().forEach((track) => track.stop());
            stream = undefined;
            video.srcObject = null;
            modal.hidden = true;
        };

        openCamera.addEventListener('click', async () => {
            modal.hidden = false;
            capturePhoto.disabled = true;
            message.textContent = translate('requestingCamera');

            if (!navigator.mediaDevices?.getUserMedia) {
                message.textContent = translate('unsupportedCamera');
                return;
            }

            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                video.srcObject = stream;
                message.textContent = '';
                capturePhoto.disabled = false;
            } catch {
                message.textContent = translate('unavailableCamera');
            }
        });

        capturePhoto.addEventListener('click', () => {
            if (!stream) return;

            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                if (!blob) return;

                const file = new File([blob], 'camera-profile-photo.jpg', { type: 'image/jpeg' });
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
                showPreview(file);
                stopCamera();
            }, 'image/jpeg', 0.9);
        });

        closeCamera.addEventListener('click', stopCamera);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) stopCamera();
        });
        window.addEventListener('pagehide', stopCamera);
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProfilePhoto, { once: true });
} else {
    initProfilePhoto();
}