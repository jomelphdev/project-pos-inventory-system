import _ from "lodash";
import store from "./store";
import { v4 as uuidv4 } from "uuid";

export function getConditionName(id) {
  if (!id) return "None";

  return store.getters.conditions.find((c) => {
    return c.id === id;
  }).name;
}

export function getConditionClass(id) {
  if (!id) return "";

  let condition = this.getConditionName(id);
  switch (condition) {
    case "New":
      return "rr-pill--new";
    case "Used":
      return "rr-pill--used";
    case "Damaged":
      return "rr-pill--damaged";
    default:
      break;
  }
}

export function getClassificationName(id) {
  if (!id) return null;
  const classification = store.getters.classifications.find((c) => {
    return c.id === id;
  });

  return classification ? classification.name : null;
}

export function getStoreName(id) {
  if (!id) return null;
  const store = getStore(id);

  return store ? store.name : null;
}

export function getStore(id) {
  const storeToGet = store.getters.stores.find((s) => {
    return s.id == id;
  });

  return storeToGet ? storeToGet : null;
}

export function getDiscount(id) {
  const discount = store.getters.discountsVisible.find((d) => d.id == id);
  return discount ? discount : null;
}

export function getUsersName(id) {
  if (id == store.getters.currentUser.id) {
    return store.getters.currentUser.full_name;
  }

  return store.getters.employees.find((e) => e.id == id).full_name;
}

export function imageCdn(product, params = null) {
  if (!params) {
    params = "w=640&h=480&t=fit";
  }

  // If a single image is passed
  // TODO: Make this better
  if (typeof product === "string" || product instanceof String) {
    return `https://images.weserv.nl/?url=${product}&${params}`;
  } else if (this.imageAvailable(product)) {
    return `https://images.weserv.nl/?url=${product.images[0]}&${params}`;
  }
}

export function imageAvailable(product) {
  return product.images.length > 0;
}

export function formatCurrency(number) {
  return (number / 100).toLocaleString("en-US", {
    style: "currency",
    currency: "USD",
  });
}

export function mapQuantities(quantities, userId) {
  let qtyArray = quantities
    .filter((q) => q.quantity_received != 0)
    .map((q) => {
      let message = q.message;
      if (q.quantity_received > 0 && !q.existBefore) {
        message = "Quantity Created";
      } else if (q.quantity_received > 0) {
        message = "Quantity Added";
      }

      return {
        store_id: q.store_id,
        created_by: userId,
        quantity_received: q.quantity_received,
        message: message,
      };
    });
  return qtyArray;
}

export function mapOrderItems(items) {
  return items.map((i) => {
    const item = i.added_item ? i.added_item : i.item;
    return Object.assign({}, item, i);
  });
}

export function generateUsername(first, last = null) {
  // TODO: filter special characters, spaces, etc.
  let digits = Math.floor(100 + Math.random() * 999);

  if (last) {
    return new String(`${first}${last}${digits}`).toLowerCase();
  } else if (!first) {
    return "";
  }

  return new String(`${first}${digits}`).toLowerCase();
}

export function amountClass(amount) {
  let theAmount = parseInt(amount);
  let amountClass = [];

  if (theAmount === 0) {
    amountClass.push("text-black");
  }

  if (theAmount > 0) {
    amountClass.push("rr-amount__positive");

    if (theAmount > 99) {
      amountClass.push("rr-amount__positive--100");
    }

    if (theAmount > 999) {
      amountClass.push("rr-amount__positive--1000");
    }
  }

  if (theAmount < 0) {
    amountClass.push("rr-amount__negative");
    if (theAmount < -9) {
      amountClass.push("rr-amount__negative--10");
    }

    if (theAmount < -99) {
      amountClass.push("rr-amount__negative--100");
    }

    if (theAmount < -999) {
      amountClass.push("rr-amount__negative--1000");
    }
  }

  return amountClass.join(" ");
}

export function capitalizeFirstLetter(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

export function mergeList(existingList, listToMerge) {
  return _.map(listToMerge, function (item) {
    let source = _.find(existingList, { id: item.id });
    if (source) return _.extend(source, item);

    existingList.push(item);

    return item;
  });
}

export function dbDateString(date) {
  return date.toISOString().replace(/[TZ]/g, " ");
}

export function dbDateStringNow() {
  return dbDateString(new Date());
}

export function generateQrCode() {
  const currentDate = new Date().toISOString().replace(/[:.]+/g, "-"); // Convert current date to a string and replace non-alphanumeric characters with '-'
  const uuid = uuidv4();
  return `${uuid}-${currentDate}`;
}
