import axios from 'axios'

if(localStorage.getItem('token')){
    axios.defaults.headers.common['Authorization'] = localStorage.getItem('token');
}
axios.defaults.baseURL = 'https://alex.polan.sk';
