// 通过 Socket.io 客户端发起 WebSocket 请求
import io from 'socket.io-client'
const socket = io.connect(process.env.APP_URL + ':' + process.env.LARAVELS_LISTEN_PORT + '/ws/')
export default socket
