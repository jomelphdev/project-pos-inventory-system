<template>
  <div class="qz-tray--form-element">
    <!-- Label -->
    <label
      class="qz-tray--form-element--input qz-tray--input-label"
      :for="'qz-tray-options-' + index"
      v-if="item.type === 'label'"
      >{{ item.value }}</label
    >

    <!-- Text -->
    <input
      class="qz-tray--form-element--input qz-tray--input-text"
      type="text"
      :id="'qz-tray-options-' + index"
      :name="'qz-tray-options-' + index"
      v-model="itemLocal.value"
      v-else-if="item.type === 'text'"
    />

    <!-- Number -->
    <input
      class="qz-tray--form-element--input qz-tray--input-number"
      type="number"
      :id="'qz-tray-options-' + index"
      :name="'qz-tray-options-' + index"
      v-model="itemLocal.value"
      v-else-if="item.type === 'number'"
    />

    <!-- Checkbox -->
    <input
      class="qz-tray--form-element--input qz-tray--input-checkbox"
      type="checkbox"
      :id="'qz-tray-options-' + index"
      :name="'qz-tray-options-' + index"
      v-model="itemLocal.value"
      v-else-if="item.type === 'checkbox'"
    />

    <!-- Select -->
    <select
      class="qz-tray--form-element--input qz-tray--input-select"
      :id="'qz-tray-options-' + index"
      :name="'qz-tray-options-' + index"
      v-model="itemLocal.value"
      v-else-if="item.type === 'select'"
    >
      <option v-if="item.value === null" value="" v-once disabled
        >Please select one
      </option>
      <option
        v-for="(optionLabel, option) in item.options"
        :key="option"
        class="qz-tray--input-select-option"
        :value="option"
      >
        {{ optionLabel }}
      </option>
    </select>

    <!-- Object -->
    <qz-tray-form-nested
      class="qz-tray--form-element--input qz-tray--input-object"
      :items="item.value"
      v-else-if="item.type === 'object'"
    ></qz-tray-form-nested>

    <!-- Value -->
    <span
      class="qz-tray--form-element--input qz-tray--input-value"
      :id="'qz-tray-options-' + index"
      :name="'qz-tray-options-' + index"
      v-else
      >{{ item.value }}</span
    >
  </div>
</template>

<script>
export default {
  name: "QzTrayFormElement",

  components: {
    QzTrayFormNested: () => import("./qz-tray-form-nested.vue")
  },

  props: {
    index: String,
    item: Object
  },

  data() {
    return {
      itemLocal: {}
    };
  },

  mounted() {
    this.itemLocal = this.item;
  }

  // TODO: Emit itemLocal if this component is ever used.
};
</script>

<style lang="scss" scoped>
.qz-tray--form-element {
  display: block;
  position: relative;

  width: 100%;

  margin: 0;
  padding: 0;

  -webkit-box-sizing: border-box;
  -moz-box-sizing: border-box;
  box-sizing: border-box;

  .qz-tray--form-element--input {
    display: block;
    position: relative;

    width: 100%;

    margin: 0;

    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box;

    &.qz-tray--input-label {
      margin: 0;

      font-size: 0.8em;
      font-weight: 600;
      line-height: 1.484374;
      cursor: text;

      border: 0 none;
      border-top: 1px solid transparent; // Height hack - label gets input height
    }

    &.qz-tray--input-object {
      margin-top: 0;
      margin-bottom: 0;

      &::v-deep .qz-tray--form-nested--table-row:first-child {
        border-top: 0 none;

        .qz-tray--form-nested--table-cell {
          padding-top: 0;
        }
      }
    }
  }
}
</style>
