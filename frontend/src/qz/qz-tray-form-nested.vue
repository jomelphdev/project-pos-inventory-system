<template>
  <div class="qz-tray--form-nested">
    <table class="qz-tray--form-nested--table">
      <tbody>
        <tr
          class="qz-tray--form-nested--table-row"
          v-for="(item, index) in items"
          v-show="!item.hidden"
          :key="index"
        >
          <th
            class="qz-tray--form-nested--table-cell qz-tray--form-nested--table-cell--label"
          >
            <slot name="label" :item="item" :index="index">
              <qz-tray-form-element
                :item="{ value: item.label, type: 'label' }"
                :index="index"
              ></qz-tray-form-element>
            </slot>
          </th>

          <td class="qz-tray--form-nested--table-cell">
            <div class="qz-tray--form-nested--input">
              <slot :name="item.type" :item="item" :index="index">
                <qz-tray-form-element
                  :item="item"
                  :index="index"
                ></qz-tray-form-element>
              </slot>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: "QzTrayFormNested",

  components: {
    QzTrayFormElement: () => import("./qz-tray-form-element")
  },

  props: {
    items: Object
  }
};
</script>

<style lang="scss" scoped>
.qz-tray--form-nested {
  position: relative;
  -webkit-box-sizing: border-box;
  -moz-box-sizing: border-box;
  box-sizing: border-box;
  display: block;
  width: 100%;
  padding: 0;
  margin: 0;

  .qz-tray--form-nested--table {
    display: table;
    width: 100%;
    border-collapse: collapse;

    .qz-tray--form-nested--table-row {
      border-top: 1px solid #d3d3d3;

      &:first-child {
        border-top: 0 none;
      }
    }

    .qz-tray--form-nested--table-cell {
      display: table-cell;
      padding: 6px 0;
    }

    th.qz-tray--form-nested--table-cell {
      text-align: left;
      vertical-align: top;
    }
  }
}
</style>
