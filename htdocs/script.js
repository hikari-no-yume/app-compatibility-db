(function () {
    "use strict";

    // Convert <date> content to local timezone, but with ISO 8601 format
    // because the default locale date formats suck.
    function processDateTime(time) {
        var date = new Date(time.dateTime);
        var local = "";
        local += date.getFullYear().toString();
        local += "-";
        local += ("00" + (date.getMonth() + 1)).substr(-2);
        local += "-";
        local += ("00" + date.getDate()).substr(-2);
        local += " ";
        local += ("00" + date.getHours()).substr(-2);
        local += ":";
        local += ("00" + date.getMinutes()).substr(-2);
        local += ":";
        local += ("00" + date.getSeconds()).substr(-2);
        time.textContent = local;
        // Let the user see the original date by hovering over it.
        time.title = time.dateTime;
    }

    // Enable image uploads. JavaScript is used to do client-side image resizing
    // and encoding, which not only simplifies the server-side code, but also
    // reduces the amount of data uploaded and protects the user's privacy
    // (since EXIF metadata etc is never transmitted).
    //
    // `field` is an <input type=hidden> that'll be used to submit the data URI
    // to the server.
    function processImageUploadField(field) {
        var fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*';

        var clearButton = document.createElement('button');
        clearButton.textContent = 'Clear';

        var preview = new Image;
        preview.title = preview.alt = 'Image preview';

        field.parentElement.insertBefore(fileInput, field.nextSibling);
        field.parentElement.insertBefore(clearButton, fileInput.nextSibling);
        field.parentElement.insertBefore(preview, clearButton.nextSibling);

        function clearFile() {
            field.value = '';
            preview.src = '';
            preview.style.display = 'none';
            fileInput.value = null;
            clearButton.disabled = true;
        }

        function setProcessedImage(dataUri) {
            field.value = dataUri;
            preview.src = dataUri;
            preview.style.display = 'inline-block';
            clearButton.disabled = false;
        }

        function processImage() {
            try {
                var source = this;

                var MAX_SIZE = 640; // in pixels, on either side
                var JPEG_QUALITY = 0.8; // 80%

                var largestSide = Math.max(source.width, source.height);
                var scale = Math.min(1, MAX_SIZE / largestSide);

                console.log("Source image is " + source.width + "×" + source.height);

                // Browsers often don't use mipmapping when downscaling images,
                // which means that scales of less than 0.5× can produce
                // noticeable aliasing artifacts. To avoid that, reduce in 0.5×
                // steps if necessary.
                while (scale < 0.5) {
                    var newSource = document.createElement('canvas');
                    newSource.width = source.width / 2;
                    newSource.height = source.height / 2;
                    console.log("0.5× reduction step: " + source.width + "×" + source.height + " → " + newSource.width + "×" + newSource.height);

                    var newSourceCtx = newSource.getContext('2d');
                    newSourceCtx.drawImage(source, 0, 0, newSource.width, newSource.height);

                    source = newSource;
                    scale *= 2;
                }

                var dest = document.createElement('canvas');
                dest.width = source.width * scale;
                dest.height = source.height * scale;

                var destCtx = dest.getContext('2d');
                destCtx.drawImage(source, 0, 0, dest.width, dest.height);

                var dataUri = dest.toDataURL('image/jpeg', JPEG_QUALITY);

                let sizeEstimate = dataUri.length * (6/8); // base64 compensation
                console.log('Processed image: ' + dest.width + '×' + dest.height + ', ' + Math.ceil(sizeEstimate / 1000) + ' KB');
                setProcessedImage(dataUri);
            } catch (e) {
                alert("Couldn't process image: " + e);
                clearFile();
            }
        }

        clearButton.onclick = function (e) {
            e.preventDefault();
            clearFile();
            return false;
        };
        fileInput.onchange = function () {
            try {
                var file = fileInput.files[0];
                if (!file) {
                    clearFile();
                    return;
                }

                var source = new Image;
                source.onload = processImage;
                source.onerror = function (e) {
                    alert("Couldn't load image");
                    clearFile();
                };
                source.src = URL.createObjectURL(file);
            } catch (e) {
                alert("Couldn't load image: " + e);
                clearFile();
            }
        };

        clearFile();
    }

    document.addEventListener("DOMContentLoaded", function () {
        var times = document.getElementsByTagName("time");
        for (var i = 0; i < times.length; i++) {
            processDateTime(times[i]);
        }

        var imageFields = document.getElementsByClassName('image-upload');
        for (var i = 0; i < imageFields.length; i++) {
            processImageUploadField(imageFields[i]);
        }
    });
}());
