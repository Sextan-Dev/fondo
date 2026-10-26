import Login from './components/login/Login.vue'; 
import ForgotPassword from './components/login/forgotPassword.vue';
import PasswordReset from './components/login/passwordReset.vue';
import Home from './components/home/Home.vue';
import UserList from './components/administration/users/UserList.vue';
import UserForm from './components/administration/users/UserForm.vue';
import History from './components/administration/History.vue';
import Calendar from './components/common/calendar.vue';
import Directory from './components/common/directory/directoryList.vue';
import DirectoryForm from './components/common/directory/directoryForm.vue';
import Parameters from './components/administration/parameters/parameters.vue';
import Configuration from './components/administration/configuration/configuration.vue';
import OperationForm from './components/accounting/operationForm.vue';
import OperationList from './components/accounting/operationList.vue';
import ReportOperationsDonors from './components/accounting/reportOperationsDonors.vue';
import ReportOperationsDonor from './components/accounting/reportOperationsDonor.vue';
import OperationsDonorsMonitoring from './components/accounting/operationsDonorsMonitoring.vue';
import OperationsDonorsReport from './components/accounting/operationsDonorsReport.vue';
import LibraryForm from './components/common/library/libraryForm.vue'; 
import LibraryList from './components/common/library/libraryList.vue';
import ProjectForm from './components/projects/projectForm.vue';
import ProjectList from './components/projects/projectList.vue';
import ProjectReport from './components/reports/projectReport.vue';
import ProjectQuote from './components/reports/projectQuote.vue';
import ReportOperations from './components/reports/reportOperations.vue';
import ReportPublic from './components/reports/reportPublic.vue';
import AccountForm from './components/accounting/accountForm.vue';
import AccountList from './components/accounting/accountList.vue';
import ProgramList from './components/projects/programList.vue';
import TaskList from './components/tasks/taskList.vue';
import TaskForm from './components/tasks/taskForm.vue';
import GoodsAndServicesForm from './components/accounting/goodsAndServicesForm.vue';
import GoodsAndServicesList from './components/accounting/goodsAndServicesList.vue';
import DonorsList from './components/donors/donorsList.vue';
import DonorsForm from './components/donors/donorsForm.vue';
import DonorsAccount from './components/donors/donorsAccount.vue';
import DonorsReport from './components/reports/donorsReport.vue';
import DonorReport from './components/reports/donorReport.vue';
import AllyReport from './components/reports/allyReport.vue';
import AlliesList from './components/allies/alliesList.vue';
import AlliesForm from './components/allies/alliesForm.vue';
import PageNotFound  from './components/errors/404.vue';
import documentOC from './components/reports/documentOC.vue';
import documentOP from './components/reports/documentOP.vue';
import TimeLineNotifications from './components/notifications/timeLineNotifications';
import OperationHistory from './components/accounting/operationHistory.vue';

import DonorsBalanceReport from './components/reports/donorsBalanceReport.vue';

export default [
	//LOGIN
	{
		path: '/login',
		name: 'login',
		component: Login,
		meta: {requiresGuest: true}
	},
	{
		path: '/forgot/password',
		name: 'ForgotPassword',
		component: ForgotPassword,
		meta: {requiresGuest: true}
	},
	{
		path: '/password/reset/:token',
		name: 'PasswordReset',
		component: PasswordReset,
		meta: {requiresGuest: true}
	},
	//HOME
	{
		path: '/',
		name: 'index',
		component: Home,
		meta: {requiresAuth: true}
	},
	// PROGRAMS
	{
		path: '/program/list',
		name: 'ProgramList',
		component: ProgramList,
		meta: {requiresAuth: true}
	},
	// PROJECTS
	{
		path: '/project/list',
		name: 'ProjectList',
		component: ProjectList,
		meta: {requiresAuth: true}
	},
	{
		path: '/project/form/:id?',
		name: 'ProjectForm',
		component: ProjectForm,
		meta: {requiresAuth: true}
	},
	// TASKS
	{
		path: '/task/list',
		name: 'TaskList',
		component: TaskList,
		meta: {requiresAuth: true}
	},
	{
		path: '/task/form',
		name: 'TaskForm',
		component: TaskForm,
		meta: {requiresAuth: true}
	},
	//USERS
	{
		path: '/user/list',
		name: 'UserList',
		component: UserList,
		meta: {requiresAuth: true}
	},
	{
		path: '/user/form',
		name: 'UserForm',
		component: UserForm,
		meta: {requiresAuth: true}
	},

	//REPORTS
	{
		path: '/report/project',
		name: 'ProjectReport',
		component: ProjectReport,
		meta: {requiresAuth: true}
	},
	
	{
		path: '/report/operations',
		name: 'ReportOperations',
		component: ReportOperations,
		meta: {requiresAuth: true}
	},
	{
		path: '/public/report',
		name: 'ReportPublic',
		component: ReportPublic,
		//meta: {requiresGuest: true}
	},
	{
		path: '/report/project/quote',
		name: 'ProjectQuote',
		component: ProjectQuote,
		meta: {requiresAuth: true}
	},
	{
		path: '/report/donors',
		name: 'DonorsReport',
		component: DonorsReport,
		meta: {requiresAuth: true}
	},
	{
		path: '/report/donor/:id',
		name: 'DonorReport',
		component: DonorReport,
		//meta: {requiresGuest: true}
	},
	{
		path: '/report/ally/:id',
		name: 'AllyReport',
		component: AllyReport,
		//meta: {requiresGuest: true}
	},

	//PARAMETERS
	{
		path: '/parameters',
		name: 'Parameters',
		component: Parameters,
		meta: {requiresAuth: true}
	},

	
	//CONFIGURATION	
	{
		path: '/configuration',
		name: 'Configuration',
		component: Configuration,
		meta: {requiresAuth: true}
	},

	// OPERATIONS
	{
		path: '/operation/list',
		name: 'OperationList',
		component: OperationList,
		meta: {requiresAuth: true}
	},
	{
		path: '/operation/form/:id?',
		name: 'OperationForm',
		component: OperationForm,
		meta: {requiresAuth: true}
	},
	{
		path: '/operation/donors',
		name: 'ReportOperationsDonors',
		component: ReportOperationsDonors,
		meta: {requiresAuth: true}
	},
	{
		path: '/operation/donor/:id',
		name: 'ReportOperationsDonor',
		component: ReportOperationsDonor,
		meta: {requiresAuth: true}
	},
	{
		path: '/operation/donors/monitoring',
		name: 'OperationsDonorsMonitoring',
		component: OperationsDonorsMonitoring,
		meta: {requiresAuth: true}
	},
	{
		path: '/operation/donors/report',
		name: 'OperationsDonorsReport',
		component: OperationsDonorsReport,
		meta: {requiresAuth: true}
	},
	{
		path: '/operation/history',
		name: 'OperationHistory',
		component: OperationHistory,
		meta: {requiresAuth: true}
	},
	// {
	// 	path: '/operation/donor/report/:id',
	// 	name: 'OperationsDonorReport',
	// 	component: OperationsDonorReport,
	// 	meta: {requiresAuth: true}
	// },
	// ACCOUNTING
	{
		path: '/account/form',
		name: 'AccountForm',
		component: AccountForm,
		meta: {requiresAuth: true}
	},
	{
		path: '/account/list',
		name: 'AccountList',
		component: AccountList,
		meta: {requiresAuth: true}
	},
	{
		path: '/good/and/services/form',
		name: 'GoodsAndServicesForm',
		component: GoodsAndServicesForm,
		meta: {requiresAuth: true}
	},
	{
		path: '/good/and/services/list',
		name: 'GoodsAndServices',
		component: GoodsAndServicesList,
		meta: {requiresAuth: true}
	},
	// COMMON

	//CALENDAR
	{
		path: '/calendar',
		name: 'Calendar',
		component: Calendar,
		meta: {requiresAuth: true}
	},
	//HISTORY
	{
		path: '/history',
		name: 'History',
		component: History,
		meta: {requiresAuth: true}
	},
	//DIRECTORY
	{
		path: '/directory',
		name: 'Directory',
		component: Directory,
		meta: {requiresAuth: true}
	},
	{
		path: '/directory/form',
		name: 'DirectoryForm',
		component: DirectoryForm,
		meta: {requiresAuth: true}
	},
	//DONORS
	{
		path: '/donors',
		name: 'DonorsList',
		component: DonorsList,
		meta: {requiresAuth: true}
	},
	{
		path: '/donors/form/:id?',
		name: 'DonorsForm',
		component: DonorsForm,
		meta: {requiresAuth: true}
	},
	{
		path: '/donors/account',
		name: 'DonorsAccount',
		component: DonorsAccount,
		meta: {requiresAuth: true}
	},
	{
		path: '/donors/balance/report',
		name: 'DonorsBalanceReport',
		component: DonorsBalanceReport,
		meta: {requiresAuth: true}
	},
	//ALLIES
	{
		path: '/allies',
		name: 'AlliesList',
		component: AlliesList,
		meta: {requiresAuth: true}
	},
	{
		path: '/allies/form',
		name: 'AlliesForm',
		component: AlliesForm,
		meta: {requiresAuth: true}
	},
	// DOCUMENTS
	{
		path: '/library',
		name: 'Library',
		component: LibraryList,
		meta: {requiresAuth: true}
	},
	{
		path: '/library/form',
		name: 'LibraryForm',
		component: LibraryForm,
		meta: {requiresAuth: true}
	},


	// NOTIFICATIONS
	{path: '/notifications/timeline', name: 'TimeLineNotifications', component: TimeLineNotifications, meta: {requiresAuth: true}	}, 
	
	
	//404
	{ 	
		path: "*",
		name: 'PageNotFound',
		component: PageNotFound,
		meta: {requiresAuth: true}
	},
	//404
	{ 	
		path: "/document/order/oc",
		name: 'documentOC',
		component: documentOC,
		meta: {requiresAuth: true}
	},
	//404
	{ 	
		path: "/document/order/op",
		name: 'documentOP',
		component: documentOP,
		meta: {requiresAuth: true}
	},
];