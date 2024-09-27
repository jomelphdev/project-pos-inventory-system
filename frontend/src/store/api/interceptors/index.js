import store from "@/store/index";
import axios from "axios";
// import router from "./router";

export const setRequestInterceptor = () => {
  axios.interceptors.request.use(
    request => {
      // TODO: Add a global loading state
      // store.dispatch('startLoading');
      request.headers.Authorization = store.getters.token;
      request.headers.Accept = "application/json";
      request.headers["Content-Type"] = "application/json";

      return request;
    },
    error => Promise.reject(error)
  );
};

export const setResponseInterceptor = () => {
  axios.interceptors.response.use(
    response => {
      // TODO: Add a global loading finished state
      // store.dispatch('endLoading');
      return response;
    },
    err => {
      const {
        // store.dispatch('endLoading');
        response: { status }
      } = err;

      if (status === 401) {
        // store.dispatch('endLoading');
        store.dispatch("logout");
      }
      throw err.response;
    }
  );
};
