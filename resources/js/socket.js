// 通过 Socket.io 客户端发起 WebSocket 请求
import io from 'socket.io-client';
const socket = io('http://webchats.test', {
    path: '/ws',
    transports: ['websocket']
});
export default socket;
