Dropzone.autoDiscover = false;

const myDropzone = new Dropzone("#my-dropzone-form", {
    autoProcessQueue: false,  // Prevent auto processing, we will submit manually
    parallelUploads: 10,
    addRemoveLinks: true,
    init: function () {
        console.log("Dropzone initialized"); // This will print in the console when Dropzone is initialized

        const submitBtn = document.getElementById("submit-proof");
        const dz = this;

        submitBtn.addEventListener("click", function () {
            const reason = document.getElementById("reason").value;

            if (dz.getQueuedFiles().length === 0) {
                alert("Please add at least one file.");
                return;
            }

            dz.options.params = { reason: reason };

            dz.processQueue(); // Start uploading the files
        });

        
        dz.on("sending", function(file, xhr, formData) {
            const reason = document.getElementById("reason").value;
            formData.append("reason", reason);
        });

        dz.on("successmultiple", function(files, response) {
            alert("Files uploaded successfully!");
        });

        dz.on("errormultiple", function(files, response) {
            alert("Error uploading files.");
        });

        // Ensure that the "Remove File" button works
        dz.on("removedfile", function(file) {
            console.log("File removed: " + file.name);
        });
    }
});
