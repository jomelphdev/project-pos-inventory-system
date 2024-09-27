<template>
  <div>
    <label class="rr-field__label">
      Image
      <span class="rr-field__label-optional" v-if="imagesLocal.length > 0">
        Select images to keep
      </span>
    </label>
    <div class="mt-4 mb-6 flex" data-test="item-images">
      <div
        v-for="(image, index) in imagesLocal"
        :key="index"
        class="bg-no-repeat bg-center border-2 border-gray-400 shadow-sm rounded-md cursor-pointer mr-4"
        :class="{ '!border-blue-600 !shadow-md': isSelected(image) }"
        style="
          width: 100px;
          height: 100px;
          mix-blend-mode: multiply;
          background-size: 75px;
        "
        :style="`background-image:url(${imageCdn(
          image,
          'w=150&h=150&t=fit'
        )});`"
        :data-test="`item-image-${index}`"
        @click="selectImage(image)"
      >
        <img
          :src="imageCdn(image, 'w=150&h=150&t=fit')"
          @error="imageUrlError(image)"
          v-show="false"
        />
      </div>

      <div
        @drop.prevent="uploadImage"
        @dragover.prevent="imageUpload.dragging = true"
        @dragleave.prevent="imageUpload.dragging = false"
        data-test="item-images-upload-div"
        class="bg-no-repeat bg-white bg-center border-2 shadow-sm rounded-md mr-4 flex items-center text-center px-3"
        :class="dragClass"
        style="width: 100px; height: 100px; background-size: 75px"
      >
        <span
          class="text-xs leading-snug text-gray-500"
          v-text="imageUploadText"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { imageCdn } from "@/helpers";

export default {
  name: "ItemImages",

  data() {
    return {
      imagesLocal: [],
      brokenImages: [],
      imageUpload: {
        uploading: false,
        dragging: false,
      },
    };
  },

  props: {
    images: Array,
    selectedImages: {
      type: Array,
      default: () => [],
    },
  },

  computed: {
    imageUploadText() {
      let text = "Drag new image here.";
      if (this.imageUpload.uploading) {
        text = "Uploading image...";
      }
      if (this.imageUpload.dragging) {
        text = "Drop image to upload.";
      }
      return text;
    },

    dragClass() {
      return this.imageUpload.dragging
        ? "border-gray-500 border-solid"
        : "border-gray-400 border-dotted";
    },

    brokenImageInSelected() {
      for (let image of this.brokenImages) {
        if (this.selectedImages.includes(image)) {
          return true;
        }
      }

      return false;
    },
  },

  watch: {
    images: {
      handler() {
        this.initImages();
      },
      immediate: true,
    },
    selectedImages() {
      this.$emit(
        "set-deleted-images",
        this.images.filter((img) => !this.selectedImages.includes(img))
      );
    },
  },

  mounted() {
    this.$root.$on("refresh-item", () => {
      this.initImages();
    });

    this.assignCypress();
  },

  methods: {
    initImages() {
      let images = this.images
        .filter((image) => {
          return this.brokenImages.indexOf(image) < 0;
        })
        .slice(0, 5);

      if (
        !this.selectedImages ||
        this.selectedImages.length == 0 ||
        this.brokenImageInSelected
      ) {
        this.$emit("set-images", JSON.parse(JSON.stringify(images)));
      }

      this.imagesLocal = images;
      this.assignCypress();
    },

    uploadImage(event) {
      this.imageUpload.uploading = true;
      this.imageUpload.dragging = false;
      const image = event.dataTransfer.files[0];

      this.$store.dispatch("uploadImage", image).then((imageUrl) => {
        this.imageUpload.uploading = false;
        let result = [...this.imagesLocal];

        if (this.imagesLocal.length >= 5) {
          if (this.selectedImages.length < 5) {
            const index = this.imagesLocal.findIndex(
              (img) => !this.selectedImages.includes(img)
            );
            result = [...this.imagesLocal].splice(index, 1, imageUrl);
          } else {
            result.pop();
            result.push(imageUrl);
          }
        } else {
          result.push(imageUrl);
        }

        this.imagesLocal = result;
        this.$emit("set-images", result);
      });
    },
    // TODO: Better broken image handling
    // Works decent, but maybe there's a library or we extract this into a helper.
    imageUrlError(image) {
      this.brokenImages.push(image);
      this.initImages();
    },

    selectImage(image) {
      let index = this.selectedImages.findIndex((i) => i == image);
      let result = [...this.selectedImages];

      if (
        !this.selectedImages.includes(image) &&
        this.selectedImages.length < 5
      ) {
        index = this.imagesLocal.findIndex((img) => img == image);
        result.splice(index, 0, image);
      } else {
        result.splice(index, 1);
      }

      this.$emit("set-images", result);
    },

    isSelected(image) {
      if (image) {
        return this.selectedImages.indexOf(image) != -1;
      }
    },

    assignCypress() {
      if (window.Cypress) {
        window.ItemImages = {
          selectedImages: this.selectedImages,
          images: this.imagesLocal,
        };
      }
    },

    imageCdn,
  },
};
</script>
