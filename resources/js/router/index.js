import Vue from 'vue';
import Router from 'vue-router';
import Index from '../pages/Loan';
import Chat from '../pages/Chat';
import Robot from '../pages/Robot';
import Home from '../pages/Home';
import Avatar from '../pages/Avatar';
import Login from '../pages/Login';
import Register from '../pages/Register';
import BaseTransition from '../layout/BaseTransition';
import loading from '../components/loading';

// 通过 Vue Router 定义前端路由
Router.prototype.goBack = function () {
    this.isBack = true;
    window.history.go(-1);
};
Vue.use(Router);

const router = new Router({
    routes: [
        {
            path: '/',
            name: 'BaseTransition',
            component: BaseTransition,
            children: [
                {
                    path: '',
                    name: 'index',
                    component: Index
                },
                {
                    path: '/chat',
                    name: 'chat',
                    component: Chat
                },
                {
                    path: '/robot',
                    name: 'Robot',
                    component: Robot
                }
            ]
        },
        {
            path: '/home',
            name: 'Home',
            component: Home
        },
        {
            path: '/avatar',
            name: 'avatar',
            component: Avatar
        },
        {
            path: '/register',
            name: 'Register',
            component: Register
        },
        {
            path: '/login',
            name: 'Login',
            component: Login
        }
    ]
});

router.beforeEach((to, from, next) => {
    loading.show();
    next();
});

router.afterEach(route => {
    loading.hide();
});

export default router;
