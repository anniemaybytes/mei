create table files_map
(
	`Key` varchar(199) not null,
	FileName varchar(199) not null primary key,
	UploaderId int not null,
	UploadTime datetime not null,
	Protected int not null,
	TorrentId int not null
);
