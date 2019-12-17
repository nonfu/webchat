import Axios from './axios';

const Service = {
    // 登录接口
    loginUser: data => Axios.post('/login', data),
    // 注册接口
    RegisterUser: data => Axios.post('/register', data),
    // 获取当前房间所有历史聊天记录
    RoomHistoryAll: data => Axios.get('/history/message', {
        params: data
    }),
    // 机器人
    getRobotMessage: data => Axios.get('/robot', {
        params: data
    }),
    // 上传图片
    postUploadFile: data => Axios.post('/file/uploadimg', data, {
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    }),
    // 上传头像
    postUploadAvatar: data => Axios.post('/file/avatar', data, {
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    }),

    // 请求公告
    getNotice: () => Axios.get('https://s3.qiufengh.com/config/notice-config.js')
};

export default Service;
