angular.module('app.controllers', ['ionic', 'ui.router', 'ngCordova', 'pascalprecht.translate', 'ngStorage'])

.run(function($rootScope, $localStorage) {
	$rootScope.sejour = {};
	$rootScope.sejour.name = '';
	$rootScope.sejourId = '';
	$rootScope.login = $localStorage.login;
	$rootScope.url = 'https://alema.yoannbourgery.com/';
	$rootScope.token = $localStorage.token;
	$rootScope.role = $localStorage.role;
	$rootScope.brochure = {};
	$rootScope.brochure.season = '';
	$rootScope.brochure.type = '';
	$rootScope.cguAct = true;
})

.config(function($translateProvider) {
		$translateProvider.preferredLanguage('fr');
	})
	.controller('tabCtrl', ['$scope', '$stateParams', '$state', '$rootScope',
		function($scope, $stateParams, $state, $rootScope) {
			$scope.goGen = function() {
				$state.go('menuGen.accueil_gen');
			};

			$scope.goMembre = function() {
				if ($rootScope.login == '' || $rootScope.login === undefined)
					$state.go('alema');
				else
					$state.go('mesSejours');
			};
		}
	])

.controller('alemaCtrl', ['$scope', '$stateParams',
	function($scope, $stateParams) {


	}
])

.controller('passwordForgetCtrl', ['$scope', '$state', '$http', '$ionicPopup', '$rootScope',
	function($scope, $state, $http, $ionicPopup, $rootScope) {
		$scope.data = {};
		$scope.login = {};
		$scope.reset = function() {
			$http({
					method: 'POST',
					url: $rootScope.url + 'users/lost/' + $scope.login.login,
					data: $scope.data

				})
				.success(function(data, status, headers, config) {
					$scope.showAlert = function() {
						var alertPopup = $ionicPopup.alert({
							title: 'Valide',
							template: 'Votre mot de passe a été réinitialisé et envoyé par mail'
						});
					};
					$scope.showAlert();
					$state.go('alema');
				})
				.error(function(data, status, headers, config) {
					$scope.showAlert = function() {
						var alertPopup = $ionicPopup.alert({
							title: 'Erreur',
							template: 'Votre login ne correspond pas à votre adresse mail'
						});
					};
					$scope.showAlert();
				});
		};
	}
])

.controller('photosCtrl', ['$scope', '$stateParams', '$rootScope', '$ionicModal', '$ionicSlideBoxDelegate', '$cordovaCamera', '$cordovaFile', '$cordovaFileTransfer', '$cordovaDevice', '$ionicPopup', '$cordovaActionSheet', '$http', '$state',

	function($scope, $stateParams, $rootScope, $ionicModal, $ionicSlideBoxDelegate, $cordovaCamera, $cordovaFile, $cordovaFileTransfer, $cordovaDevice, $ionicPopup, $cordovaActionSheet, $http, $state) {
		$scope.options = {
			loop: false,
			effect: 'coverflow',
			speed: 500,
			zoom: true,
			pagination: false
		};
		$scope.$on("$ionicSlides.sliderInitialized", function(event, data) {
			// data.slider is the instance of Swiper
			$scope.slider = data.slider;
			$scope.slider.activeIndex = $scope.pictureIndex;

		});
		$scope.$on("$ionicView.enter", function(event, data) {
						$scope.load = false;
			if ($scope.slider !== undefined) {
							}
			$http({
					method: 'GET',
					url: $rootScope.url + 'trips/' + $rootScope.sejourId + '/pictures',
					data: $scope.data,
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$scope.pictures = data;
					for (var i = 0; i < $scope.pictures.length; i++) {
						$scope.pictures[i].pictureName = $rootScope.url + "uploads/pictures/" + $scope.pictures[i].pictureName;
						$scope.pictures[i].nbLike = $scope.pictures[i].likePicture.length;
						$scope.pictures[i].like = 0;
						for (var j = 0; j < $scope.pictures[i].likePicture.length; j++) {
							if ($scope.pictures[i].likePicture[j].loginUser.login == $rootScope.login) {
								$scope.pictures[i].like = 1;
							}
						}
					}
				})
				.error(function(data, status, headers, config) {});
			if ($rootScope.reloadImg === 1) {
				$rootScope.reloadImg = 0;
			}
		});
		$scope.loadImage = function() {
			var options = {
				title: 'Choisissez votre image',
				buttonLabels: ['Utiliser la bibliothèque', 'Utiliser l\'appareil'],
				addCancelButtonWithLabel: 'Annuler',
				androidEnableCancelButton: true,
			};
			$cordovaActionSheet.show(options).then(function(btnIndex) {
				var type = null;
				if (btnIndex === 1) {
					type = Camera.PictureSourceType.PHOTOLIBRARY;
				} else if (btnIndex === 2) {
					type = Camera.PictureSourceType.CAMERA;
				}
				if (type !== null) {
					$scope.selectPicture(type);
				}
			});
		};
		$scope.selectPicture = function(sourceType) {
			var options = {
				quality: 75,
				destinationType: Camera.DestinationType.FILE_URI,
				sourceType: sourceType,
				saveToPhotoAlbum: false,
				correctOrientation: true
			};

			$cordovaCamera.getPicture(options).then(function(imagePath) {
					// Grab the file name of the photo in the temporary directory
					var currentName = imagePath.replace(/^.*[\\\/]/, '');

					//Create a new name for the photo
					var d = new Date(),
						n = d.getTime(),
						newFileName = n + ".jpg";

					// If you are trying to load image from the gallery on Android we need special treatment!
					if ($cordovaDevice.getPlatform() == 'Android' && sourceType === Camera.PictureSourceType.PHOTOLIBRARY) {
						window.FilePath.resolveNativePath(imagePath, function(entry) {
							window.resolveLocalFileSystemURL(entry, success, fail);

							function fail(e) {
								console.error('Error: ', e);
							}

							function success(fileEntry) {
								var namePath = fileEntry.nativeURL.substr(0, fileEntry.nativeURL.lastIndexOf('/') + 1);
								// Only copy because of access rights
								$cordovaFile.copyFile(namePath, fileEntry.name, cordova.file.dataDirectory, newFileName).then(function(success) {
									$scope.image = newFileName;
								}, function(error) {
									$scope.showAlert('Error', error.exception);
								});
							};
						});
					} else {
						var namePath = imagePath.substr(0, imagePath.lastIndexOf('/') + 1);
						// Move the file to permanent storage
						$cordovaFile.moveFile(namePath, currentName, cordova.file.dataDirectory, newFileName).then(function(success) {
							$scope.image = newFileName;
							$scope.uploadImage();
						}, function(error) {
							$scope.showAlert('Error', error.exception);
						});
					}
				},
				function(err) {
					// Not always an error, maybe cancel was pressed...
				})
		};
		$scope.pathForImage = function(image) {
			if (image === null) {
				return '';
			} else {
				return cordova.file.dataDirectory + image;
			}
		};
		$scope.uploadImage = function() {
			// Destination URL
			$scope.load = true;
			var url = $rootScope.url + 'trips/' + $rootScope.sejourId + '/pictures';

			// File for Upload
			var targetPath = $scope.pathForImage($scope.image);
						// File name only
			var filename = $scope.image;;

			var options = {
				fileKey: "pictureName",
				fileName: filename,
				chunkedMode: false,
				mimeType: "image/jpg",
				httpMethod: "POST",
				headers: {
					'X-Auth-Token': $rootScope.token
				},
				params: {
					'pictureName': filename
				}
			};

			$cordovaFileTransfer.upload(url, targetPath, options).then(function(result) {
				$scope.showAlert = function() {
					var alertPopup = $ionicPopup.alert({
						title: 'Upload',
						template: 'Votre photo a bien été enregistré'
					});
				};
				$scope.load = false;
				$scope.showAlert();
				$state.reload();
			});
		};
		$scope.showImages = function(index) {
			$scope.pictureIndex = index;
			$scope.showModal('templates/membre/afficheImage.html');
		};

		$scope.showModal = function(templateUrl) {
						$ionicModal.fromTemplateUrl(templateUrl, {
				scope: $scope,
				animation: 'slide-in-up'
			}).then(function(modal) {
				$scope.modalImg = modal;
				$scope.modalImg.show();
			});
		}

		$scope.closeModal = function() {
			$scope.modalImg.hide();
			$scope.modalImg.remove();
		};

		$scope.showCommentaires = function(index) {
			$scope.closeModal();
			$state.go('menu.commentaires');
		};

		$scope.deleteImg = function() {
			$scope.load = true;
			for (var i = 0; i < $scope.pictures.length; i++) {
				if ($scope.pictures[i].delete == 'yes') {
					$http({
							method: 'DELETE',
							url: $rootScope.url + 'pictures/' + $scope.pictures[i].id,
							data: $scope.data,
							headers: {
								'X-Auth-Token': $rootScope.token
							}
						})
						.success(function(data, status, headers, config) {
							$scope.load = false;
							$state.reload();
						})
						.error(function(data, status, headers, config) {});
				}
			}

		}

		$scope.addLike = function(id, index) {
						$http({
					method: 'POST',
					url: $rootScope.url + 'pictures/' + id + "/like-pictures",
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$scope.closeModal();
					$state.reload();
				})
				.error(function(data, status, headers, config) {});
		}

		$scope.removeLike = function(id, index) {
			$http({
					method: 'DELETE',
					url: $rootScope.url + 'pictures/' + id + "/like-pictures",
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$scope.closeModal();
					$state.reload();

				})
				.error(function(data, status, headers, config) {});
		}

	}
])

.controller('commentaireCtrl', ['$scope', '$rootScope', '$http', '$state',
	function($scope, $rootScope, $http, $state) {
		$scope.$on("$ionicView.enter", function(event, data) {
			$http({
					method: 'GET',
					url: $rootScope.url + 'trips/' + $rootScope.sejourId + '/comments',
					data: $scope.data,
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
										$scope.commentary = data;
					for (var i = 0; i < $scope.commentary.length; i++) {
						if ($scope.commentary[i].loginUser.login == $rootScope.login) {
							$scope.commentary[i].own = 1;
						} else {
							$scope.commentary[i].own = 0;
						}
					}
				})
				.error(function(data, status, headers, config) {});
		});
		$scope.data = {};
		$scope.comment = function() {
			$http({
					method: 'POST',
					url: $rootScope.url + 'trips/' + $rootScope.sejourId + '/comments',
					data: $scope.data,
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$scope.data.text = '';
					$state.reload();
				})
				.error(function(data, status, headers, config) {});
		}
		$scope.deleteCom = function(id) {
			$http({
					method: 'DELETE',
					url: $rootScope.url + 'comments/' + id,
					data: $scope.data,
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$state.reload();
				})
				.error(function(data, status, headers, config) {});
		}
		$scope.deleteListCom = function() {
			for (var i = 0; i < $scope.commentary.length; i++) {
				if ($scope.commentary[i].delete == 'yes') {
					$scope.deleteCom($scope.commentary[i].id);
				}
			}
		}
	}
])

.controller('actualitSCtrl', ['$scope', '$state', '$http', '$rootScope',
	function($scope, $state, $http, $rootScope) {
		$scope.$on("$ionicView.enter", function(event, data) {
			$http({
					method: 'GET',
					url: $rootScope.url + 'trips/' + $rootScope.sejourId + '/actualities',
					data: $scope.data,
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$scope.actualities = data;
					for (var i = 0; i < $scope.actualities.length; i++) {
						$scope.actualities[i].pictureName = $rootScope.url + "uploads/actualities/" + $scope.actualities[i].pictureName;
						$scope.actualities[i].viewDelete = false;
						$scope.actualities[i].nbLike = $scope.actualities[i].likeActuality.length;
						$scope.actualities[i].like = 0;
						if ($scope.actualities[i].text.length > 150) {
							$scope.actualities[i].readMore = 1;
						}
						else{
							$scope.actualities[i].readMore = 0;
						}
						for (var j = 0; j < $scope.actualities[i].likeActuality.length; j++) {
							if ($scope.actualities[i].likeActuality[j].loginUser.login == $rootScope.login) {
								$scope.actualities[i].like = 1;
							}
						}
					}
				})
				.error(function(data, status, headers, config) {});
		});
		$scope.displayActuality = function(id) {
			$rootScope.actuality = {};
			$rootScope.actuality = $scope.actualities[id];
			$state.go('showActuality');
		};
		$scope.displayDeleteActuality = function(id) {
			$scope.actualities[id].viewDelete = true;
		}
		$scope.hideDeleteActuality = function(id) {
			$scope.actualities[id].viewDelete = false;
		}
		$scope.deleteActuality = function(id) {
			$http({
					method: 'DELETE',
					url: $rootScope.url + 'actualities/' + $scope.actualities[id].id,
					data: $scope.data,
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$state.reload();
				})
				.error(function(data, status, headers, config) {});
		}
		$scope.addLike = function(id, index) {
			$http({
					method: 'POST',
					url: $rootScope.url + 'actualities/' + id + "/like-actualities",
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$state.reload();
				})
				.error(function(data, status, headers, config) {});
		}

		$scope.removeLike = function(id, index) {
			$http({
					method: 'DELETE',
					url: $rootScope.url + 'actualities/' + id + "/like-actualities",
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$state.reload();

				})
				.error(function(data, status, headers, config) {});
		}
		$scope.showCommentaires = function() {
			$state.go('menu.commentaires');
		};
	}
])

.controller('addActualityCtrl', ['$scope', '$stateParams', '$rootScope', '$ionicModal', '$ionicSlideBoxDelegate', '$cordovaCamera', '$cordovaFile', '$cordovaFileTransfer', '$cordovaDevice', '$ionicPopup', '$cordovaActionSheet', '$http', '$state',
	function($scope, $stateParams, $rootScope, $ionicModal, $ionicSlideBoxDelegate, $cordovaCamera, $cordovaFile, $cordovaFileTransfer, $cordovaDevice, $ionicPopup, $cordovaActionSheet, $http, $state) {
		$scope.data = {};
		$scope.load = false;
		$scope.loadImage = function() {
			var options = {
				title: 'Choisissez votre image',
				buttonLabels: ['Utiliser la bibliothèque', 'Utiliser l\'appareil'],
				addCancelButtonWithLabel: 'Annuler',
				androidEnableCancelButton: true,
			};
			$cordovaActionSheet.show(options).then(function(btnIndex) {
				var type = null;
				if (btnIndex === 1) {
					type = Camera.PictureSourceType.PHOTOLIBRARY;
				} else if (btnIndex === 2) {
					type = Camera.PictureSourceType.CAMERA;
				}
				if (type !== null) {
					$scope.selectPicture(type);
				}
			});
		};
		$scope.selectPicture = function(sourceType) {
			var options = {
				quality: 75,
				destinationType: Camera.DestinationType.FILE_URI,
				sourceType: sourceType,
				saveToPhotoAlbum: false,
				correctOrientation: true
			};

			$cordovaCamera.getPicture(options).then(function(imagePath) {
					// Grab the file name of the photo in the temporary directory
					var currentName = imagePath.replace(/^.*[\\\/]/, '');

					//Create a new name for the photo
					var d = new Date(),
						n = d.getTime(),
						newFileName = n + ".jpg";

					// If you are trying to load image from the gallery on Android we need special treatment!
					if ($cordovaDevice.getPlatform() == 'Android' && sourceType === Camera.PictureSourceType.PHOTOLIBRARY) {
						window.FilePath.resolveNativePath(imagePath, function(entry) {
							window.resolveLocalFileSystemURL(entry, success, fail);

							function fail(e) {
								console.error('Error: ', e);
							}

							function success(fileEntry) {
								var namePath = fileEntry.nativeURL.substr(0, fileEntry.nativeURL.lastIndexOf('/') + 1);
								// Only copy because of access rights
								$cordovaFile.copyFile(namePath, fileEntry.name, cordova.file.dataDirectory, newFileName).then(function(success) {
									$scope.image = newFileName;
								}, function(error) {
									$scope.showAlert('Error', error.exception);
								});
							};
						});
					} else {
						var namePath = imagePath.substr(0, imagePath.lastIndexOf('/') + 1);
						// Move the file to permanent storage
						$cordovaFile.moveFile(namePath, currentName, cordova.file.dataDirectory, newFileName).then(function(success) {
							$scope.image = newFileName;
						}, function(error) {
							$scope.showAlert('Error', error.exception);
						});
					}
				},
				function(err) {
					// Not always an error, maybe cancel was pressed...
				})
		};
		$scope.pathForImage = function(image) {
			if (image === null) {
				return '';
			} else {
				return cordova.file.dataDirectory + image;
			}
		};
		$scope.uploadActuality = function() {
			// Destination URL
			$scope.load = true;
			var url = $rootScope.url + 'trips/' + $rootScope.sejourId + '/actualities';

			// File for Upload
			var targetPath = $scope.pathForImage($scope.image);
			// File name only
			var filename = $scope.image;;

			var options = {
				fileKey: "pictureName",
				fileName: filename,
				chunkedMode: false,
				mimeType: "image/jpg",
				httpMethod: "POST",
				headers: {
					'X-Auth-Token': $rootScope.token
				},
				params: {
					'pictureName': filename,
					'title': $scope.data.title,
					'text': $scope.data.text
				}
			};

			$cordovaFileTransfer.upload(url, targetPath, options).then(function(result) {
				$scope.showAlert = function() {
					var alertPopup = $ionicPopup.alert({
						title: 'Upload',
						template: 'Votre actualité a bien été ajouté'
					});
				};
				$scope.load = false;
				$scope.showAlert();
				$state.go('menu.actualities');
			});
		};
	}
])

.controller('showActualityCtrl', ['$scope', '$state', '$http', '$rootScope',
	function($scope, $state, $http, $rootScope) {
		$scope.$on("$ionicView.enter", function(event, data) {
			$scope.actuality = $rootScope.actuality;
		});
		$scope.addLike = function(id, index) {
			$http({
					method: 'POST',
					url: $rootScope.url + 'actualities/' + id + "/like-actualities",
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$rootScope.actuality.nbLike++;
					$rootScope.actuality.like = 1;
					$state.reload();
				})
				.error(function(data, status, headers, config) {});
		}

		$scope.removeLike = function(id, index) {
			$http({
					method: 'DELETE',
					url: $rootScope.url + 'actualities/' + id + "/like-actualities",
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$rootScope.actuality.nbLike--;
					$rootScope.actuality.like = 0;
					$state.reload();

				})
				.error(function(data, status, headers, config) {});
		}
		$scope.showCommentaires = function() {
			$state.go('menu.commentaires');
		};
	}
])

.controller('informationCtrl', ['$scope', '$http', '$rootScope',
	function($scope, $http, $rootScope) {
		$scope.$on("$ionicView.enter", function(event, data) {
			$http({
					method: 'GET',
					url: $rootScope.url + 'trips/' + $rootScope.sejourId,
					data: $scope.data,
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$scope.info = data;
				})
				.error(function(data, status, headers, config) {});
		});
	}
])

.controller('menuCtrl', ['$scope', '$stateParams',
	function($scope, $stateParams) {


	}
])

.controller('connexionCtrl', ['$scope', '$ionicPopup', '$state', '$rootScope', '$http', '$localStorage',
	function($scope, $ionicPopup, $state, $rootScope, $http, $localStorage) {
		$scope.load = false;
		$scope.data = {};
		$scope.remember = {};
		$scope.login = function() {
			$scope.remember.remember;
			$scope.load = true;
			$http({
					method: 'POST',
					url: $rootScope.url + 'auth-tokens',
					data: $scope.data
				})
				.success(function(data, status, headers, config) {
					$rootScope.token = data.value;
					$rootScope.login = $scope.data.login;
					$rootScope.role = data.user.roles[0];
					if ($scope.remember.remember === 'yes') {
						$localStorage.login = $rootScope.login;
						$localStorage.token = $rootScope.token;
						$localStorage.role = $rootScope.role;
					}
					$scope.load = false;
					$state.go('mesSejours');
				})
				.error(function(data, status, headers, config) {
					if(data.message === "blocked"){
						$scope.showAlert = function() {
							var alertPopup = $ionicPopup.alert({
								title: 'Interdit',
								template: 'Vous n\'avez pas accès à ce contenu, pour plus de renseignement contactez nous'
							});
						};

					}
					else{
						$scope.showAlert = function() {
							var alertPopup = $ionicPopup.alert({
								title: 'Mauvais Identifiant',
								template: 'Vos identifiants ne sont pas corrects'
							});
						};
					}
					$scope.load = false; 
					$scope.showAlert();
				});
		};
	}
])

.controller('mesSJoursCtrl', ['$scope', '$http', '$rootScope', '$state',
	function($scope, $http, $rootScope, $state) {
		$scope.$on("$ionicView.enter", function(event, data) {
						$scope.date = new Date();
			$scope.load = true;
			if ($rootScope.role !== 'ROLE_DIRECTOR') {
				$http({
						method: 'GET',
						url: $rootScope.url + 'users/' + $rootScope.login + '/participate_trips',
						data: $scope.user,
						headers: {
							'X-Auth-Token': $rootScope.token
						}
					})
					.success(function(data, status, headers, config) {
						var nb_sej = 0;
						$scope.sejours = [];
						var id_sej = [];
						for (var i = 0; i < data.length; i++) {
							for (var j = 0; j < data[i].length; j++) {
								if (id_sej.lastIndexOf(data[i][j].idTrip.id) == -1) {
									$scope.sejours.push(data[i][j].idTrip);
									$scope.sejours[nb_sej].child = [];
									$scope.sejours[nb_sej].child.push(data[i][j].idChild);
									id_sej.push(data[i][j].idTrip.id);
									nb_sej++;
								} else {
									for (var k = 0; k < $scope.sejours.length; k++) {
										if ($scope.sejours[k].id == data[i][j].idTrip.id) {
											$scope.sejours[k].child.push(data[i][j].idChild);
											break;
										}
									}
								}
							}
						}
						for (var i = 0; i < $scope.sejours.length; i++) {
							var dateStart = new Date($scope.sejours[i].dateStart);
							var dateEnd = new Date($scope.sejours[i].dateEnd);
							if (dateEnd < $scope.date) {
								$scope.sejours[i].info = "Fini";
							} else if (dateStart > $scope.date) {
								$scope.sejours[i].info = "A Venir";
							} else {
								$scope.sejours[i].info = "En cours";
							}
						}
												$scope.load = false;
					})
					.error(function(data, status, headers, config) {

					});
			} else {
				$http({
						method: 'GET',
						url: $rootScope.url + 'directors/' + $rootScope.login + '/trips',
						data: $scope.user,
						headers: {
							'X-Auth-Token': $rootScope.token
						}
					})
					.success(function(data, status, headers, config) {
						$scope.sejours = data;
						for (var i = 0; i < $scope.sejours.length; i++) {
							var dateStart = new Date($scope.sejours[i].dateStart);
							var dateEnd = new Date($scope.sejours[i].dateEnd);
							if (dateEnd < $scope.date) {
								$scope.sejours[i].info = "Fini";
							} else if (dateStart > $scope.date) {
								$scope.sejours[i].info = "A Venir";
							} else {
								$scope.sejours[i].info = "En cours";
							}
						}
						$scope.load = false;
					})
					.error(function(data, status, headers, config) {

					});
			}
		});
		$scope.change = function(id) {
			$rootScope.sejour.name = $scope.sejours[id].name;
			$rootScope.sejourId = $scope.sejours[id].id;
		};

	}
])

.controller('inscriptionCtrl', ['$scope', '$ionicPopup', '$rootScope', '$http', '$state',
	function($scope, $ionicPopup, $rootScope, $http, $state) {
		$scope.data = {};
		$scope.data.user = {};
		$scope.confirmPassword = {};
		$scope.style = {};
		$scope.cgu = {};
		var relative = false;
		$scope.signUp = function() {
			var bool = true;
			if ($scope.data.user.login === '' || $scope.data.user.login === undefined) {
				$scope.style.login = {
					'border': '1px solid red'
				};
				bool = false;
			}
			else{
				$scope.style.login = {
					'border': 'none'
				};
			}
			if ($scope.data.user.lastname === '' || $scope.data.user.lastname === undefined) {
				$scope.style.lastname = {
					'border': '1px solid red'
				};
				bool = false;
			}
			else{
				$scope.style.lastname = {
					'border': 'none'
				};
			}
			if ($scope.data.user.firstname === '' || $scope.data.user.firstname === undefined) {
				$scope.style.firstname = {
					'border': '1px solid red'
				};
				bool = false;
			}
			else{
				$scope.style.firstname = {
					'border': 'none'
				};
			}
			if ($scope.data.user.email === '' || $scope.data.user.email === undefined) {
				$scope.style.email = {
					'border': '1px solid red'
				};
				bool = false;
			}
			else{
				$scope.style.email = {
					'border': 'none'
				};
			}
			if ($scope.data.user.plainPassword === '' || $scope.data.user.plainPassword === undefined) {
				$scope.style.plainPassword = {
					'border': '1px solid red'
				};
				bool = false;
			}
			else{
				$scope.style.plainPassword = {
					'border': 'none'
				};
			}
			if ($scope.confirmPassword.confirmPassword === '' || $scope.confirmPassword.confirmPassword === undefined) {
				$scope.style.confirmPassword = {
					'border': '1px solid red'
				};
				bool = false;
			}
			else{
				$scope.style.confirmPassword = {
					'border': 'none'
				};
			}
			if ($scope.cgu.cgu === false || $scope.cgu.cgu === undefined) {
				$scope.style.cgu = {
					'border': '1px solid red'
				};
				bool = false;
			}
			else{
				$scope.style.cgu = {
					'border': 'none'
				};
			}
			if (bool) {
				if ($scope.data.user.plainPassword !== $scope.confirmPassword.confirmPassword) {
					$scope.data.user.plainPassword = '';
					$scope.confirmPassword.confirmPassword = '';
					$scope.showAlert = function() {
						var alertPopup = $ionicPopup.alert({
							title: 'Erreur',
							template: 'Vos deux mots de passes ne correspondent pas'
						});
					};
					$scope.showAlert();
				} else if ($scope.data.user.plainPassword.length < 4) {
					$scope.data.user.plainPassword = '';
					$scope.confirmPassword.confirmPassword = '';
					$scope.showAlert = function() {
						var alertPopup = $ionicPopup.alert({
							title: 'Erreur',
							template: 'Votre mot de passe doit être supérieur à 4 caractères'
						});
					};
					$scope.showAlert();
				} else {
					if ($scope.data.familyNumber === undefined || $scope.data.familyNumber === '') {
						$scope.url = $rootScope.url + 'users';
						$scope.payload = $scope.data.user;
					} else {
						$scope.url = $rootScope.url + 'relatives';
						$scope.payload = $scope.data;
						relative = true;
					}
					$http({
							method: 'POST',
							url: $scope.url,
							data: $scope.payload
						})
						.success(function(data, status, headers, config) {
							$scope.showAlert = function() {
								var alertPopup = $ionicPopup.alert({
									title: 'Inscription',
									template: 'Votre inscription a bien été effectué'
								});
							};
							$scope.showAlert();
							$state.go('alema');
						})
						.error(function(data, status, headers, config) {
							if ((!relative && data.errors.children.login.length !== 0) ||
								(relative && data.errors.children.user.children.login.length !== 0)) {
								$scope.data.user.plainPassword = '';
								$scope.confirmPassword.confirmPassword = '';
								$scope.data.user.login = '';
								$scope.showAlert = function() {
									var alertPopup = $ionicPopup.alert({
										title: 'Erreur',
										template: 'Votre login existe déjà'
									});
								};
								$scope.showAlert();
							}
							if (relative && data.errors.children.familyNumber.length !== 0) {
								$scope.data.user.plainPassword = '';
								$scope.confirmPassword.confirmPassword = '';
								$scope.data.familyNumber = '';
								$scope.showAlert = function() {
									var alertPopup = $ionicPopup.alert({
										title: 'Erreur',
										template: 'Votre numéro de famille est déjà utilisé'
									});
								};
								$scope.showAlert();
							}
						});

				}
			}
		};
	}
])

.controller('sJourCtrl', ['$scope', '$stateParams', '$rootScope',
	function($scope, $stateParams, $rootScope) {
		$scope.sejour = $rootScope.sejour.name;
	}
])

.controller('compteCtrl', ['$scope', '$state', '$http', '$rootScope', '$localStorage',
	function($scope, $state, $http, $rootScope, $localStorage) {
		$scope.$on("$ionicView.enter", function(event, data) {
			$scope.data = {};
			if ($rootScope.role === "ROLE_USER" || $rootScope.role === "ROLE_DIRECTOR") {
				$http({
						method: 'GET',
						url: $rootScope.url + 'users/' + $rootScope.login,
						headers: {
							'X-Auth-Token': $rootScope.token
						}
					})
					.success(function(data, status, headers, config) {
						$scope.data.user = data;
					})
					.error(function(data, status, headers, config) {

					});
			} else {
				$http({
						method: 'GET',
						url: $rootScope.url + 'relatives/' + $rootScope.login,
						headers: {
							'X-Auth-Token': $rootScope.token
						}
					})
					.success(function(data, status, headers, config) {
						$scope.data = data;
					})
					.error(function(data, status, headers, config) {

					});
			}
		});
		$scope.manageChild = function(id) {
			$rootScope.idChild = id;
			$state.go('manageChild');
		};

		$scope.deco = function() {
			delete $localStorage.token;
			delete $localStorage.login;
			delete $localStorage.role;
			$http({
					method: 'DELETE',
					url: $rootScope.url + 'auth-tokens/' + $rootScope.login,
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {

				})
				.error(function(data, status, headers, config) {

				});
			$rootScope.login = '';
			$rootScope.token = '';
			$rootScope.role = '';
			$state.go('alema');
		}
	}
])

.controller('modifProfilCtrl', ['$scope', '$state', '$rootScope', '$http', '$ionicPopup', '$localStorage',
	function($scope, $state, $rootScope, $http, $ionicPopup, $localStorage) {
		$scope.$on("$ionicView.enter", function(event, data) {
			$scope.data = {};
			if ($rootScope.role === "ROLE_USER" || $rootScope.role === "ROLE_DIRECTOR") {
				$http({
						method: 'GET',
						url: $rootScope.url + 'users/' + $rootScope.login,
						headers: {
							'X-Auth-Token': $rootScope.token
						}
					})
					.success(function(data, status, headers, config) {
						$scope.data.user = data;
					})
					.error(function(data, status, headers, config) {

					});
			} else {
				$http({
						method: 'GET',
						url: $rootScope.url + 'relatives/' + $rootScope.login,
						headers: {
							'X-Auth-Token': $rootScope.token
						}
					})
					.success(function(data, status, headers, config) {
						$scope.data = data;
					})
					.error(function(data, status, headers, config) {

					});
			}
		});
		$scope.validModifProfil = function() {
			$scope.payload = {};
			$scope.payload.user = {};
			$scope.payload.user.firstname = $scope.data.user.firstname;
			$scope.payload.user.lastname = $scope.data.user.lastname;
			$scope.payload.user.email = $scope.data.user.email;
			if ($rootScope.role === "ROLE_USER" || $rootScope.role === "ROLE_DIRECTOR") {
				$http({
						method: 'PATCH',
						url: $rootScope.url + 'users/' + $rootScope.login,
						data: $scope.payload.user,
						headers: {
							'X-Auth-Token': $rootScope.token
						}
					})
					.success(function(data, status, headers, config) {
						if ($scope.data.familyNumber !== undefined && $scope.data.familyNumber !== '') {
							$scope.change = {};
							$scope.change.familyNumber = $scope.data.familyNumber;
							$http({
									method: 'POST',
									url: $rootScope.url + 'changeInRelative/' + $rootScope.login,
									data: $scope.change,
									headers: {
										'X-Auth-Token': $rootScope.token
									}
								})
								.success(function(data, status, headers, config) {
									$localStorage.role = "ROLE_RELATIVE";
									$rootScope.role = "ROLE_RELATIVE";
								})
								.error(function(data, status, headers, config) {
									$scope.data.familyNumber = '';
									$scope.showAlert = function() {
										var alertPopup = $ionicPopup.alert({
											title: 'Erreur',
											template: 'Votre numéro de famille est déjà utilisé'
										});
									};
									$scope.showAlert();
								});
						}
						$state.go('compte');

					})
					.error(function(data, status, headers, config) {

					});
			} else {
				$scope.payload.familyNumber = $scope.data.familyNumber;
				$http({
						method: 'PATCH',
						url: $rootScope.url + 'relatives/' + $rootScope.login,
						data: $scope.payload,
						headers: {
							'X-Auth-Token': $rootScope.token
						}
					})
					.success(function(data, status, headers, config) {
						$state.go('compte');
					})
					.error(function(data, status, headers, config) {
						$scope.data.familyNumber = '';
						$scope.showAlert = function() {
							var alertPopup = $ionicPopup.alert({
								title: 'Erreur',
								template: 'Votre numéro de famille est déjà utilisé'
							});
						};
						$scope.showAlert();
					});
			}
		};
	}
])

.controller('modifPasswordCtrl', ['$scope', '$state', '$rootScope', '$http', '$ionicPopup',
	function($scope, $state, $rootScope, $http, $ionicPopup) {
		$scope.data = {};
		$scope.data.user = {};
		$scope.confirm = {};
		$scope.changePassword = function() {
			if ($scope.data.user.plainPassword !== $scope.confirm.confirm) {
				$scope.data.user.plainPassword = '';
				$scope.confirm.confirm = '';
				$scope.showAlert = function() {
					var alertPopup = $ionicPopup.alert({
						title: 'Erreur',
						template: 'Vos deux mots de passes ne correspondent pas'
					});
				};
				$scope.showAlert();
			}
			$http({
					method: 'PATCH',
					url: $rootScope.url + 'users/changePassword/' + $rootScope.login,
					data: $scope.data.user,
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$state.go('compte');
				})
				.error(function(data, status, headers, config) {
					$scope.data.user.oldPassword = '';
					$scope.data.user.plainPassword = '';
					$scope.confirm.confirm = '';
					$scope.showAlert = function() {
						var alertPopup = $ionicPopup.alert({
							title: 'Erreur',
							template: 'Votre ancien mot de passe est incorrect'
						});
					};
					$scope.showAlert();
				});

		};
	}
])

.controller('brochureGenCtrl', ['$scope', '$state', '$rootScope', '$http',
	function($scope, $state, $rootScope, $http) {
		$scope.brochure = {};
		$scope.brochure.season = '';
		$scope.season = function(season){
			if (season == 0) {
				$scope.brochure.season = "Eté";
				$rootScope.brochure.season = "Eté";

			}
			else{
				$scope.brochure.season = 'Hiver';
				$rootScope.brochure.season = 'Hiver';
			}
		};
	}
])

.controller('askBrochureGenCtrl', ['$scope', '$state', '$rootScope', '$http','$ionicPopup',
	function($scope, $state, $rootScope, $http, $ionicPopup) {
		$scope.data = {};
		$scope.style = {};
		$scope.sendBrochure = function() {
			if($rootScope.brochure.season === "Hiver"){
				$scope.url = "brochure-winter";
			}
			else{
				$scope.url = "brochure-summer";
			}
			if($scope.data.email === undefined || $scope.data.email === ''){
				$scope.style.email = {
					'border': '1px solid red'
				};
				return;
			}
			$http({
				method: 'POST',
				url: $rootScope.url + $scope.url,
				data: $scope.data,
				headers: {
					'X-Auth-Token' : $rootScope.token
				} 
			})
			.success(function(data, status, headers, config) {
					$scope.showAlert = function() {
						var alertPopup = $ionicPopup.alert({
							title: 'Envoi réussi',
							template: 'La brochure a bien été envoyée !'
						});
					};
					$scope.showAlert();
					$state.go('menuGen.accueil_gen');
			})
			.error(function(data, status, headers, config) {
					$scope.showAlert = function() {
						var alertPopup = $ionicPopup.alert({
							title: 'Erreur',
							template: 'Une erreur s\'est produite '
						});
					};
					$scope.showAlert();
			});
		};
		$scope.sendBrochurePoste = function() {
			var bool = true;
			if($rootScope.brochure.season === "Hiver"){
				$scope.url = "brochure-winter";
			}
			else{
				$scope.url = "brochure-summer";
			}
			if($scope.data.lastname === undefined || $scope.data.lastname === ''){
				$scope.style.lastname = {
					'border': '1px solid red'
				};
				bool = false;
			}
			if($scope.data.firstname === undefined || $scope.data.firstname === ''){
				$scope.style.firstname = {
					'border': '1px solid red'
				};
				bool = false;
			}
			if($scope.data.address === undefined || $scope.data.address === ''){
				$scope.style.address = {
					'border': '1px solid red'
				};
				bool = false;
			}
			if($scope.data.city === undefined || $scope.data.city === ''){
				$scope.style.city = {
					'border': '1px solid red'
				};
				bool = false;
			}
			if($scope.data.postcode === undefined || $scope.data.postcode === ''){
				$scope.style.postcode = {
					'border': '1px solid red'
				};
				bool = false;
			}
			if(bool){
				$http({
					method: 'POST',
					url: $rootScope.url + $scope.url,
					data: $scope.data,
					headers: {
						'X-Auth-Token' : $rootScope.token
					} 
				})
				.success(function(data, status, headers, config) {
						$scope.showAlert = function() {
							var alertPopup = $ionicPopup.alert({
								title: 'Envoi réussi',
								template: 'La brochure a bien été envoyée !'
							});
						};
						$scope.showAlert();
						$state.go('menuGen.accueil_gen');
				})
				.error(function(data, status, headers, config) {
						$scope.showAlert = function() {
							var alertPopup = $ionicPopup.alert({
								title: 'Erreur',
								template: 'Une erreur s\'est produite '
							});
						};
						$scope.showAlert();
				});
			}
		};
	}
])

.controller('manageChildCtrl', ['$scope', '$state', '$http', '$rootScope', '$ionicPopup',
	function($scope, $state, $http, $rootScope, $ionicPopup) {
		$scope.$on("$ionicView.enter", function(event, data) {
			$scope.idChild = $rootScope.idChild;
			$scope.add = {};
			$scope.login = {};
			$scope.edit = false;
			$http({
					method: 'GET',
					url: $rootScope.url + 'children/' + $scope.idChild + '/access_children',
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$scope.data = data;
				})
				.error(function(data, status, headers, config) {});
		});
		$scope.deleteRelationChild = function(id) {
			$http({
					method: 'DELETE',
					url: $rootScope.url + 'access_children/' + id,
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$state.reload();
				})
				.error(function(data, status, headers, config) {});
		};
		$scope.modifRelationChild = function(id, index) {
			$scope.change = {};
			$scope.change.familyLink = $scope.data[index].familyLink;
						$http({
					method: 'PATCH',
					url: $rootScope.url + 'access_children/' + id,
					data: $scope.change,
					headers: {
						'X-Auth-Token': $rootScope.token
					}
				})
				.success(function(data, status, headers, config) {
					$state.reload();
				})
				.error(function(data, status, headers, config) {});
		};
		$scope.addRelation = function(idChild) {
			var bool = true;
			if ($scope.add.familyLink === undefined || $scope.add.familyLink === '') {
				$scope.myStyleRelation = {
					'border': '1px solid red'
				};
				bool = false;
			}
			if ($scope.login.login === undefined || $scope.login.login === '') {
				$scope.myStyleLogin = {
					'border': '1px solid red'
				};
				bool = false;
			}
			if (bool) {
				$http({
						method: 'POST',
						url: $rootScope.url + 'users/' + $scope.login.login + '/access_children/' + $scope.idChild,
						data: $scope.add,
						headers: {
							'X-Auth-Token': $rootScope.token
						}
					})
					.success(function(data, status, headers, config) {
						$state.reload();
					})
					.error(function(data, status, headers, config) {
						$scope.login.login = '';
						if (data.message === "User not found") {
							$scope.showAlert = function() {
								var alertPopup = $ionicPopup.alert({
									title: 'Erreur',
									template: 'L\'utilisateur n\'existe pas'
								});
							};
						} else {
							$scope.showAlert = function() {
								var alertPopup = $ionicPopup.alert({
									title: 'Erreur',
									template: 'Ce lien existe déjà'
								});
							};
						}
						$scope.showAlert();
					});
			}
		}
	}
])

.controller('accueilGenCtrl', ['$scope', '$state', '$http', '$rootScope', '$ionicModal',
	function($scope, $state, $http, $rootScope, $ionicModal) {
	
	}
])

.controller('menuGenCtrl', ['$scope', '$stateParams',
	function($scope, $stateParams) {


	}
])


.controller('cguCtrl', ['$scope', '$rootScope', '$state',
	function($scope, $rootScope, $state) {
		$scope.back = function(){
			if($rootScope.cguAct === true){
				$state.go("menuGen.accueil_gen");
			}
			else{
				$state.go("inscription");
			}
		};

	}
])

.controller('partenaireGenCtrl', ['$scope', '$rootScope', '$state', '$http',
	function($scope, $rootScope, $state, $http) {
		$scope.$on("$ionicView.enter", function(event, data) {
			$http({
					method: 'GET',
					url: $rootScope.url + 'parteners'
				})
				.success(function(data, status, headers, config) {
					$scope.parteners = data;
				})
				.error(function(data, status, headers, config) {});
		});
	}

])



.controller('sejoursCtrl', ['$scope', '$http', '$rootScope', '$state',
	function($scope, $http, $rootScope, $state) {
		$scope.$on("$ionicView.enter", function(event, data) {
			$scope.data = {};
			$http({
					method: 'GET',
					url: $rootScope.url + 'trips',
				})
				.success(function(data, status, headers, config) {
					$scope.sejours_gen = data;
					$scope.load = false;
				})
				.error(function(data, status, headers, config) {
									});
		});

	}
])