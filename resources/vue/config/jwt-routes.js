const apiDomain = Laravel.apiDomain;
export const api = {

	//MAIN
	login: apiDomain + '/authenticate',
	logout: apiDomain + '/logout',
	sendMailResetPassword: apiDomain + '/forgot/password',
	resetPassword: apiDomain + '/reset/password',
	userSession: apiDomain + '/get/session',
	refreshToken: apiDomain + '/refresh-token',

	//USERs
	listUser : apiDomain + '/user/list',
	dataUser  : apiDomain + '/user/data',
	showUser : apiDomain + '/user/show',
	createUser : apiDomain + '/user/store',
	updateUser : apiDomain + '/user/update',
	deleteUser : apiDomain + '/user/delete',

	//EVENTs
	indexEvent : apiDomain + '/event/index',
	showEvent : apiDomain + '/event/show',
	storeEvent : apiDomain + '/event/store',
	updateEvent : apiDomain + '/event/update',
	deleteEvent : apiDomain + '/event/delete',

	//CONTACTS
	indexContact : apiDomain + '/contact/index',
	showContact : apiDomain + '/contact/show',
	dataContact : apiDomain + '/contact/data',
	selectContact : apiDomain + '/contact/select',
	select2Contact : apiDomain + '/contact/select2',
	storeContact : apiDomain + '/contact/store',
	updateContact : apiDomain + '/contact/update',
	deleteContact : apiDomain + '/contact/delete',
	sendNotificationContact : apiDomain + '/contact/send/notification',
	storeContactTransaction: apiDomain + '/contact/store/transaction',
	listCategory : apiDomain + '/contact/listCategory',

	//ACTIONs
	indexAction : apiDomain + '/action/index',
	showAction : apiDomain + '/action/show',
	selectAction : apiDomain + '/action/select',

	// PARAMETERS
	listingParameters : apiDomain + '/parameters/listing',
	storeParameters : apiDomain + '/parameters/store',
	updateParameters : apiDomain + '/parameters/update',
	deleteParameter : apiDomain + '/parameters/delete',
	showParameters : apiDomain + '/parameters/show',
	updateParametersCategories :apiDomain + '/parameters/update/categories',

	//CONFIGURATION
	showCompany: apiDomain + '/configuration/company',
	updateCompany: apiDomain + '/configuration/update/company',

	//OPERATIONS
	listOperation : apiDomain + '/operation/index',
	selectOperation : apiDomain + '/operation/select',
	selectOperationDonors : apiDomain + '/operation/donors/select',
	selectOperationDonorsMonitoring : apiDomain + '/operation/donors/monitoring/select',
	dataOperation : apiDomain + '/operation/data',
	storeOperation : apiDomain + '/operation/store',
	deleteOperation : apiDomain + '/operation/delete',
	replicateOperation : apiDomain + '/operation/replicate',
	showOperation : apiDomain + '/operation/show',
	updateOperation : apiDomain + '/operation/update',
	uploadOperations : apiDomain + '/operation/upload',
	balanceAccountOperations : apiDomain + '/operation/balance/account',
	balanceAccountsDetailsOperations : apiDomain + '/operation/balance/accounts/details',
	historyOperation : apiDomain + '/operation/history/select',
	historyOperationDelete : apiDomain + '/operation/history/delete',

	// IMPORTS OPERATIONS
	importMasiveProjects : apiDomain + '/operation/import/masive/projects',
	importMasiveIncomes : apiDomain + '/operation/import/masive/incomes',
	importMasiveOutcomes : apiDomain + '/operation/import/masive/outcomes',
	importMasiveTransfers : apiDomain + '/operation/import/masive/transfers',


	//ACCOUNTS
	listAccount : apiDomain + '/account/index',
	createAccount : apiDomain + '/account/store',
	deleteAccount : apiDomain + '/account/delete',
	showAccount : apiDomain + '/account/show',
	updateAccount : apiDomain + '/account/update',

	// PROGRAMS
	listingProgram : apiDomain + '/program/listing',
	storeProgram: apiDomain + '/program/store',
	updateProgram: apiDomain + '/program/update',

	//PROJECTS
	listingProject : apiDomain + '/project/listing',
	selectProject : apiDomain + '/project/select',
	dataProject : apiDomain + '/project/data',
	storeProject : apiDomain + '/project/store',
	deleteProject : apiDomain + '/project/delete',
	duplicateProject : apiDomain + '/project/duplicate',
	showProject : apiDomain + '/project/show',
	updateProject : apiDomain + '/project/update',
	showProgram : apiDomain + '/project/showProgram',
	removeAttachmentProject: apiDomain + '/project/remove/attachment',
	validateContaProject : apiDomain + '/project/validateConta',

	//TASKS
	selectTask : apiDomain + '/task/select',
	selectTaskReminder : apiDomain + '/task/select/reminder',
	selectTaskPivot : apiDomain + '/task/select/pivot',
	selectDataTaskOperations : apiDomain + '/task/data/operations',
	selectTaskOperations : apiDomain + '/task/select/operations',
	dataTask : apiDomain + '/task/data',
	storeTask : apiDomain + '/task/store',
	duplicateTask : apiDomain + '/task/duplicate',
	deleteTask : apiDomain + '/task/delete',
	deleteTaskStep : apiDomain + '/task/delete/step',
	showTask : apiDomain + '/task/show',
	updateTask: apiDomain + '/task/update',
	updateTaskGeneral: apiDomain + '/task/update/general',
	updateTaskContacts: apiDomain + '/task/update/contacts',
	updateTaskDocuments : apiDomain + '/task/update/documents',
	updateTaskOperations : apiDomain + '/task/update/operations',
	updateVolunteers: apiDomain + '/task/update/volunteers',
	updateIndicators: apiDomain + '/task/update/indicators',
	transferTaskOperations: apiDomain + '/task/transfer/donations',
	
	//LIBRARY
	indexLibrary : apiDomain + '/library/index',
	showLibrary : apiDomain + '/library/show',
	dataLibrary : apiDomain + '/library/data',
	selectLibrary : apiDomain + '/library/select',
	storeLibrary : apiDomain + '/library/store',
	updateLibrary : apiDomain + '/library/update',
	deleteLibrary : apiDomain + '/library/delete',

	//REPORTS
	dataReport : apiDomain + '/report/data',
	projectReport : apiDomain + '/report/project',
	reportOperations : apiDomain + '/report/operations',
	reportPublic : apiDomain + '/report/public',
	reportDashboard : apiDomain + '/report/dashboard',
	reportDonor : apiDomain + '/report/donor',
	reportDonors : apiDomain + '/report/donors',
	dataReportDonors : apiDomain + '/report/donors/data',
	reportAlly : apiDomain + '/report/ally',
	donorsBalanceReport : apiDomain + '/donors/balance/report',
	
	// SUPPORT
	supportRequest : apiDomain + '/support/request',

	// UTILS
	deleteFile : apiDomain + '/delete/files',

	// listGoodAndServices
	listGoodAndServices: apiDomain + '/goods/and/services/list',
	selectGoodAndServices: apiDomain + '/goods/and/services/select',
	dataGoodAndServices: apiDomain + '/goods/and/services/data',
	showGoodAndServices: apiDomain + '/goods/and/services/show',
	updateGoodAndServices: apiDomain + '/goods/and/services/update',
	storeGoodAndServices: apiDomain + '/goods/and/services/store',
	deleteGoodAndServices: apiDomain + '/goods/and/services/delete',

	// ORDER PAY
	indexOrderPay: apiDomain + '/order/pay/index',
	selectOrderPay :apiDomain + '/order/pay/select',
	deleteOrderPay :apiDomain + '/order/pay/delete',
	authOrderPay :apiDomain + '/order/pay/authorization',
	dataOrderPay :apiDomain + '/order/pay/data',
	createOrderPay :apiDomain + '/order/pay/store',
	updateOrderPay :apiDomain + '/order/pay/update',
	showOrderPay :apiDomain + '/order/pay/show',
	showOrderReceivedContent:apiDomain + '/order/recived/show',

	// ORDER collect
	indexOrderCollect: apiDomain + '/order/collect/index',
	selectOrderCollect :apiDomain + '/order/collect/select',
	deleteOrderCollect :apiDomain + '/order/collect/delete',
	authOrderCollect :apiDomain + '/order/collect/authorization',
	dataOrderCollect :apiDomain + '/order/collect/data',
	createOrderCollect :apiDomain + '/order/collect/store',
	updateOrderCollect :apiDomain + '/order/collect/update',
	showOrderCollect :apiDomain + '/order/collect/show',
	
	//UTILS
	utilsUpdatePriority: apiDomain + '/content/update/priority',
	

	listCompanies: apiDomain + '/companies/list',

	// NOTIFICATIONS

	dataNotifications : apiDomain + '/notifications/data',
	sendNotification : apiDomain + '/notifications/send',
	showNotification : apiDomain + '/notifications/show',
	responseAuthNotification : apiDomain + '/notifications/responseAuth',
	listNotificationsUnread : apiDomain + '/notifications/unread',
	selectNotifications : apiDomain + '/notifications/select',
	listNotifications : apiDomain + '/notifications/list',
	deleteNotifications: apiDomain + '/notifications/delete',


	utilsShowContent: apiDomain + '/content/show',
	utilsUpdateProof: apiDomain + '/content/update/proofs',


	donorGetOperations: apiDomain + '/donor/operations/info',
	storeBalance: apiDomain + '/donor/store/operations/balance',
};