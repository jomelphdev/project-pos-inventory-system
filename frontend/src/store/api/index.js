import Axios from "axios";
import {
  setRequestInterceptor,
  setResponseInterceptor
} from "./interceptors/index";

export const configureApi = () => {
  Axios.defaults.baseURL = `${window.getters.laravelUrl}/api`;
  setRequestInterceptor();
  setResponseInterceptor();
};
