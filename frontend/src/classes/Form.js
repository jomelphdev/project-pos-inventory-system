import _ from "lodash";

class Form {
  initialData = {};
  originalData = {};

  constructor(fields) {
    this.initialData = { ...fields };
    this.originalData = { ...fields };
    this._assignFields();
  }

  // To update fields and not originalData
  fill(data) {
    for (let field in data) {
      if (field in this.originalData) {
        this[field] = data[field];
      }
    }
  }

  // Update fields and originalData
  update(data) {
    for (let field in data) {
      if (field in this.initialData) {
        this.originalData[field] = data[field];

        if (Array.isArray(data[field]) || typeof data[field] == "object") {
          data[field] = JSON.parse(JSON.stringify(data[field]));
        }

        this[field] = data[field];
      }
    }
  }

  reset() {
    this._assignFields();
  }

  initialState() {
    this.originalData = { ...this.initialData };
    this._assignFields();
  }

  _assignFields() {
    for (let field in this.originalData) {
      let fieldData = this.originalData[field];
      const fieldIsArray = Array.isArray(fieldData);

      if (typeof fieldData == "object" && !fieldIsArray) {
        fieldData = JSON.parse(JSON.stringify(fieldData));
      } else if (fieldIsArray) {
        fieldData = [...fieldData];
      }

      this[field] = fieldData;
    }
  }

  _getDirtyValuesFromArray(originalArray, currentArray) {
    return currentArray.filter((element) => {
      if (typeof element === "object" && element !== null) {
        return !originalArray.some((obj) => {
          return _.isEqual(element, obj);
        });
      }

      return !originalArray.includes(element);
    });
  }

  isKeyDirty(key) {
    return this.dirtyKeys.includes(key);
  }

  isNestedKeyDirty(nestName, index, key) {
    try {
      return Object.keys(this.dirtyData[nestName][index]).includes(key);
    } catch {
      return false;
    }
  }

  get data() {
    let data = {};

    for (let field in this.originalData) {
      data[field] = this[field];
    }

    return data;
  }

  get isDirty() {
    return Object.keys(this.dirtyData).length > 0;
  }

  get dirtyData() {
    let dirtyData = {};

    for (let field in this.originalData) {
      const originalField = this.originalData[field];
      const currentField = this[field];

      if (Array.isArray(originalField)) {
        if (currentField.length != originalField.length) {
          dirtyData[field] = this._getDirtyValuesFromArray(
            originalField,
            currentField
          );
        } else if (field == "preference_options") {
          originalField.forEach((option, index) => {
            const currentOption = currentField[index];

            for (let f in option) {
              if (option[f] != currentOption[f]) {
                if (Array.isArray(dirtyData[field])) {
                  dirtyData[field].push(currentOption);
                  continue;
                }

                dirtyData[field] = [currentOption];
                continue;
              }
            }
          });
        } else {
          const dirtyFields = this._getDirtyValuesFromArray(
            originalField,
            currentField
          );

          if (dirtyFields.length == 0) continue;

          dirtyData[field] = dirtyFields;
        }

        continue;
      } else if (typeof originalField == "object" && originalField != null) {
        for (let key in JSON.parse(JSON.stringify(originalField))) {
          if (originalField[key] != currentField[key]) {
            dirtyData[field] = currentField;
            break;
          }
        }
      } else if (currentField != originalField) {
        dirtyData[field] = currentField;
      }
    }

    return dirtyData;
  }

  get dirtyKeys() {
    return Object.keys(this.dirtyData);
  }

  // Can only delete from an array, everything else should be mutations.
  // get deletedData() {
  //   let deletedData = {};

  //   for (let field in this.originalData) {
  //     const originalField = this.originalData[field];
  //     const currentField = this[field];

  //     if (Array.isArray(originalField)) {
  //       const deletedFields = originalField.filter((element) => {
  //         if (typeof element === "object" && element !== null) {
  //           return !currentField.some(
  //             (obj) => JSON.stringify(element) === JSON.stringify(obj)
  //           );
  //         }

  //         return !currentField.includes(element);
  //       });

  //       if (deletedFields.length == 0) continue;

  //       deletedData[field] = deletedFields;
  //     }
  //   }

  //   return deletedData;
  // }
}

export default Form;
