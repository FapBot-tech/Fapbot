module.exports = {
    init: () => {
        let fetchImages = document.querySelectorAll('.image-fetch');

        let getBase64Image = async res => {
            const blob = await res.blob();

            const reader = new FileReader();

            await new Promise((resolve, reject) => {
                reader.onload = resolve;
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            });
            return reader.result;
        };


        fetchImages.forEach(image => {
            let source = image.dataset.src;

            fetch(source + '?secure=068266a20553d954dca6f', {
                // headers: {
                //     Authorization: "IfGsZpzotxjMquzU-EwVFw_aGxhxNaY5AJ1G_I1mYjs",
                // },
            })
                .then(getBase64Image)
                .then(imgString => {
                    image.src = imgString;
                });
        });
    }
};