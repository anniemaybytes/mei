CREATE TABLE `files_map` (
  `Key` varchar(199) NOT NULL,
  `FileName` varchar(199) NOT NULL,
  `UploaderId` int(11) NOT NULL,
  `UploadTime` datetime NOT NULL,
  `Protected` int(11) NOT NULL,
  `TorrentId` int(11) NOT NULL,
  PRIMARY KEY (`FileName`),
  KEY `Key` (`Key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
