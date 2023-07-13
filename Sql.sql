CREATE TABLE GCQ.dbo.G_Sql_Stores (
	ID decimal(18,0) IDENTITY(1,1) NOT NULL,
	OwnerID varchar(50) COLLATE Chinese_PRC_CI_AS NULL,
	OwnerName varchar(50) COLLATE Chinese_PRC_CI_AS NULL,
	ShortDescr varchar(300) COLLATE Chinese_PRC_CI_AS NULL,
	DetailSql varchar(MAX) COLLATE Chinese_PRC_CI_AS NULL,
	Levels int NULL,
	Types varchar(50) COLLATE Chinese_PRC_CI_AS NULL,
	ParentID decimal(18,0) NULL,
	DelFlag bit DEFAULT 0 NULL,
	CreateTime datetime DEFAULT getdate() NULL
);